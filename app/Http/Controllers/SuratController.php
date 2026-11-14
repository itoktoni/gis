<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\SuratJenisEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Divisi;
use App\Models\Surat;
use App\Models\SuratTemplate;
use App\Services\SuratExportService;
use App\Services\SuratNomorService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SuratController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(Surat $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'surat_id_divisi' => ['hasDivisi', 'divisi_nama'],
        ];
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'jenisOptions' => SuratJenisEnum::getOptions(),
            'divisiOptions' => Divisi::getCodeOptions(),
            'templateOptions' => SuratTemplate::orderBy('surat_template_kode')->get()
                ->mapWithKeys(fn ($t) => [$t->surat_template_id => $t->surat_template_kode.' - '.$t->surat_template_nama]),
        ];

        return array_merge($default, $data);
    }

    protected function baseQuery()
    {
        return $this->model->with(['hasDivisi', 'hasTemplate']);
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('surat_tanggal')->orderByDesc('surat_id');
        }
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->mergeFile($request);
        $this->autoNomor($request);

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $existing = $this->model->findOrFail($id);
        $this->mergeFile($request, $existing);

        return $this->traitPostUpdate($request, $id);
    }

    private function storeScan($file): string
    {
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('File tidak valid atau gagal diupload.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Ukuran file maksimal 5 MB.');
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            throw new \InvalidArgumentException('Format harus PDF, JPG, atau PNG.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'])) {
            throw new \InvalidArgumentException("Tipe file tidak diizinkan: {$mime}.");
        }
        $name = bin2hex(random_bytes(8)).'_'.date('YmdHis').'.'.$ext;
        $file->move(storage_path('app/public/surat'), $name);

        return 'surat/'.$name;
    }

    private function mergeFile(GeneralRequest $request, ?Surat $existing = null): void
    {
        $current = $existing?->surat_file;

        if ($request->hasFile('surat_file')) {
            try {
                $request->merge(['surat_file' => $this->storeScan($request->file('surat_file'))]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['surat_file' => $e->getMessage()]);
            }
            if ($current) {
                $old = storage_path('app/public/'.$current);
                if (file_exists($old)) {
                    unlink($old);
                }
            }

            return;
        }

        if ($request->boolean('remove_surat_file')) {
            if ($current) {
                $old = storage_path('app/public/'.$current);
                if (file_exists($old)) {
                    unlink($old);
                }
            }
            $request->merge(['surat_file' => null]);
        }
    }

    private function autoNomor(GeneralRequest $request, ?Surat $existing = null): void
    {
        if ($request->filled('surat_nomor')) {
            return;
        }

        if ($existing && $existing->surat_nomor) {
            $request->merge(['surat_nomor' => $existing->surat_nomor]);

            return;
        }

        $template = $request->filled('surat_id_template')
            ? SuratTemplate::find($request->input('surat_id_template'))
            : null;
        $divisi = $request->filled('surat_id_divisi')
            ? Divisi::find($request->input('surat_id_divisi'))
            : null;

        $tanggal = $request->input('surat_tanggal', now()->toDateString());
        $gen = app(SuratNomorService::class)->generate(
            $request->input('surat_jenis', 'KELUAR'),
            $template?->surat_template_kode,
            $divisi?->divisi_kode,
            $tanggal
        );

        $request->merge([
            'surat_nomor' => $gen['nomor'],
            'surat_urut' => $gen['urut'],
            'surat_kode_jenis' => $request->input('surat_jenis') === 'MASUK' ? 'SM' : ($template?->surat_template_kode ?? 'SK'),
        ]);

        if ($template && blank($request->input('surat_isi'))) {
            $request->merge([
                'surat_isi' => app(SuratNomorService::class)->render(
                    $template->surat_template_isi,
                    (array) $request->input('surat_data', [])
                ),
            ]);
        }
    }

    public function getGenerate(GeneralRequest $request)
    {
        $template = SuratTemplate::findOrFail($request->input('template_id', $request->input('id')));
        $placeholders = app(SuratNomorService::class)->placeholders($template->surat_template_isi);

        return $this->views($this->template('generate'), [
            'template' => $template,
            'placeholders' => $placeholders,
        ]);
    }

    public function postGenerate(GeneralRequest $request, SuratNomorService $nomor)
    {
        $template = SuratTemplate::findOrFail($request->input('surat_id_template'));

        $data = [];
        foreach ($nomor->placeholders($template->surat_template_isi) as $key) {
            $data[$key] = $request->input('data_'.$key);
        }

        $request->merge([
            'surat_jenis' => 'KELUAR',
            'surat_tanggal' => $request->input('surat_tanggal', now()->toDateString()),
            'surat_data' => $data,
            'surat_isi' => $nomor->render($template->surat_template_isi, $data),
        ]);
        $this->autoNomor($request);

        return $this->traitPostCreate($request);
    }

    public function getExportPdf(GeneralRequest $request, $id, SuratExportService $export)
    {
        $surat = $this->model->findOrFail($id);

        return $export->pdf($surat)->download('surat-'.str_replace('/', '-', $surat->surat_nomor ?? $id).'.pdf');
    }

    public function getExportDocx(GeneralRequest $request, $id, SuratExportService $export)
    {
        $surat = $this->model->findOrFail($id);
        $path = $export->docx($surat);

        return response()->download($path, 'surat-'.str_replace('/', '-', $surat->surat_nomor ?? $id).'.docx')->deleteFileAfterSend(true);
    }
}
