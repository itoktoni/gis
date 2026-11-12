<?php

namespace App\Http\Controllers;

use App\Concerns\ControllerTrait;
use App\Enums\VoucherStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Kebun;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Models\VoucherHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VoucherController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(Voucher $model)
    {
        $this->model = $model::getModel();
        $this->relationFilters = [
            'voucher_id_vendor' => ['hasVendor', ['vendor_nama', 'vendor_kode']],
            'voucher_id_kebun' => ['hasKebun', 'kebun_nama'],
        ];
    }

    protected function baseQuery()
    {
        return $this->model->with(['hasVendor', 'hasKebun']);
    }

    protected function applyDefaultOrder($query): void
    {
        if (! request()->input('sort.0')) {
            $query->orderByDesc('voucher_id');
        }
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'vendorOptions' => Vendor::getCodeOptions(),
            'kebunOptions' => Kebun::orderBy('kebun_nama')->get()->pluck('kebun_nama', 'kebun_id'),
            'statusOptions' => VoucherStatusEnum::asSelectArray(),
        ];

        return array_merge($default, $data);
    }

    public function getShow(GeneralRequest $request, $id)
    {
        $data = $this->model->with(['hasVendor', 'hasKebun', 'hasHistory.hasUser'])->findOrFail($id);

        return $this->views($this->template('show'), [
            'model' => $data,
            'nextOptions' => VoucherStatusEnum::nextOptions($data->voucher_status ?? VoucherStatusEnum::DRAFT),
        ]);
    }

    public function postCreate(GeneralRequest $request)
    {
        $file = $this->handleDocFile($request, 'voucher_file', null);
        if ($file !== null) {
            $request->merge(['voucher_file' => $file]);
        }
        if (! $request->filled('voucher_status')) {
            $request->merge(['voucher_status' => VoucherStatusEnum::DRAFT]);
        }

        return $this->traitPostCreate($request);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $voucher = $this->model->findOrFail($id);
        $file = $this->handleDocFile($request, 'voucher_file', $voucher->voucher_file);
        if ($file !== $voucher->voucher_file) {
            $request->merge(['voucher_file' => $file]);
        }

        return $this->traitPostUpdate($request, $id);
    }

    public function postStatus(GeneralRequest $request, $id)
    {
        $request->validate([
            'status_ke' => 'required|string',
            'catatan' => 'nullable|string|max:500',
            'file' => 'nullable|file|max:10240',
        ]);

        $voucher = $this->model->findOrFail($id);
        $dari = $voucher->voucher_status;
        $ke = $request->input('status_ke');

        if (! in_array($ke, VoucherStatusEnum::nextOptions($dari ?? VoucherStatusEnum::DRAFT))) {
            throw ValidationException::withMessages(['status_ke' => 'Perpindahan status tidak diizinkan.']);
        }

        if ($ke === VoucherStatusEnum::REVISI && blank($request->input('catatan'))) {
            throw ValidationException::withMessages(['catatan' => 'Alasan penolakan wajib diisi.']);
        }

        $histFile = null;
        if ($request->hasFile('file')) {
            $histFile = $this->storeVoucherFile($request->file('file'));
        }

        $voucher->update(['voucher_status' => $ke]);

        VoucherHistory::create([
            'history_id_voucher' => $voucher->voucher_id,
            'history_status_dari' => $dari,
            'history_status_ke' => $ke,
            'history_catatan' => $request->input('catatan'),
            'history_file' => $histFile,
            'history_id_user' => Auth::id(),
        ]);

        return $this->response($this->payload(TOAST_SUCCESS, $voucher));
    }

    public function postPay(GeneralRequest $request, $id)
    {
        $request->validate([
            'tanggal_bayar' => 'required|date',
            'bukti' => 'required|file|max:10240',
        ]);

        $voucher = $this->model->findOrFail($id);
        $dari = $voucher->voucher_status;

        $bukti = $this->storeVoucherFile($request->file('bukti'));

        $voucher->update([
            'voucher_status' => VoucherStatusEnum::SELESAI,
            'voucher_tanggal_bayar' => $request->input('tanggal_bayar'),
            'voucher_bukti_file' => $bukti,
        ]);

        VoucherHistory::create([
            'history_id_voucher' => $voucher->voucher_id,
            'history_status_dari' => $dari,
            'history_status_ke' => VoucherStatusEnum::SELESAI,
            'history_catatan' => 'Pembayaran selesai, bukti transfer terlampir.',
            'history_file' => $bukti,
            'history_id_user' => Auth::id(),
        ]);

        return $this->response($this->payload(TOAST_SUCCESS, $voucher));
    }

    public function getImport(GeneralRequest $request)
    {
        return $this->views($this->template('import'), []);
    }

    public function postImport(GeneralRequest $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv|max:10240']);

        $path = $request->file('file')->getRealPath();
        $sheet = IOFactory::load($path)->getActiveSheet()->toArray();
        if (count($sheet) < 2) {
            throw ValidationException::withMessages(['file' => 'File kosong.']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $sheet[0]);
        $col = function (array $names) use ($header) {
            foreach ($names as $n) {
                $i = array_search($n, $header);
                if ($i !== false) {
                    return $i;
                }
            }

            return null;
        };

        $cNo = $col(['nomor voucher', 'no voucher', 'id voucher']);
        $cVendor = $col(['kontraktor', 'vendor']);
        $cRedaksi = $col(['redaksi', 'uraian']);
        $cInv = $col(['nomor invoice', 'no invoice', 'invoice']);
        $cQty = $col(['quantity', 'qty', 'jumlah']);
        $cSat = $col(['satuan']);
        $cNilai = $col(['nilai voucher', 'nilai']);

        if ($cNo === null || $cVendor === null || $cNilai === null) {
            throw ValidationException::withMessages(['file' => 'Header tidak dikenali. Gunakan template: Nomor Voucher, Kontraktor, Redaksi, Nomor Invoice, Quantity, Satuan, Nilai Voucher.']);
        }

        $masuk = 0;
        $duplikat = 0;
        $gagal = 0;

        foreach (array_slice($sheet, 1) as $row) {
            $no = trim((string) ($row[$cNo] ?? ''));
            $vendorNama = trim((string) ($row[$cVendor] ?? ''));
            if ($no === '' || $vendorNama === '') {
                continue;
            }

            if ($this->model->where('voucher_no', $no)->exists()) {
                $duplikat++;

                continue;
            }

            try {
                $vendor = Vendor::firstOrCreate(
                    ['vendor_nama' => $vendorNama],
                    ['vendor_kode' => $this->nextVendorKode(), 'vendor_jenis' => $this->guessJenis($vendorNama)]
                );

                $nilai = (float) preg_replace('/[^0-9.]/', '', (string) ($row[$cNilai] ?? 0));
                $qtyRaw = $cQty !== null ? preg_replace('/[^0-9.]/', '', (string) ($row[$cQty] ?? '')) : '';

                $voucher = $this->model->create([
                    'voucher_no' => $no,
                    'voucher_id_vendor' => $vendor->vendor_id,
                    'voucher_no_invoice' => $cInv !== null ? trim((string) $row[$cInv]) : null,
                    'voucher_redaksi' => $cRedaksi !== null ? trim((string) $row[$cRedaksi]) : null,
                    'voucher_qty' => $qtyRaw === '' ? null : (float) $qtyRaw,
                    'voucher_satuan' => $cSat !== null ? trim((string) $row[$cSat]) : null,
                    'voucher_nilai' => $nilai,
                    'voucher_status' => VoucherStatusEnum::DRAFT,
                ]);

                VoucherHistory::create([
                    'history_id_voucher' => $voucher->voucher_id,
                    'history_status_dari' => null,
                    'history_status_ke' => VoucherStatusEnum::DRAFT,
                    'history_catatan' => 'Dibuat via import.',
                    'history_id_user' => Auth::id(),
                ]);

                $masuk++;
            } catch (\Throwable) {
                $gagal++;
            }
        }

        return $this->response($this->payload(TOAST_SUCCESS, "Import selesai: {$masuk} masuk, {$duplikat} duplikat dilewati, {$gagal} gagal."));
    }

    public function getExportExcel(GeneralRequest $request)
    {
        $data = $this->model->with(['hasVendor', 'hasKebun'])->orderByDesc('voucher_id')->get();

        return response()
            ->view('pages.voucher.excel', ['data' => $data])
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="VOUCHER-'.now()->format('Ymd').'.xls"');
    }

    private function handleDocFile(GeneralRequest $request, string $field, ?string $existing): ?string
    {
        if ($request->hasFile($field)) {
            try {
                $path = uploadDocument($request->file($field), 'voucher', ['max_size' => 10240]);
                $this->deleteVoucherFile($existing);

                return $path;
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([$field => $e->getMessage()]);
            }
        }

        if ($request->boolean('remove_'.$field)) {
            $this->deleteVoucherFile($existing);

            return null;
        }

        return $existing;
    }

    private function storeVoucherFile($file): string
    {
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('File tidak valid.');
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('Ukuran file maksimal 10 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname());
        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/zip' => null, // docx/xlsx kadang terdeteksi zip
        ];
        if (! array_key_exists($mime, $allowed)) {
            throw new \InvalidArgumentException("Tipe file tidak diizinkan: {$mime}. Gunakan PDF/JPG/PNG/DOCX/XLSX.");
        }

        $ext = $allowed[$mime] ?? strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx'])) {
            $ext = 'pdf';
        }

        $name = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8).'_'.date('YmdHis').'.'.$ext;
        $dir = storage_path('app/public/voucher');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $name);

        return 'voucher/'.$name;
    }

    private function deleteVoucherFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $file = storage_path('app/public/'.$path);
        if (is_file($file)) {
            unlink($file);
        }
    }

    private function nextVendorKode(): string
    {
        $last = Vendor::where('vendor_kode', 'like', 'VND%')->orderByDesc('vendor_kode')->value('vendor_kode');
        $n = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'VND'.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function guessJenis(string $nama): string
    {
        $upper = strtoupper($nama);
        if (str_starts_with($upper, 'PT')) {
            return 'pt';
        }
        if (str_starts_with($upper, 'CV')) {
            return 'cv';
        }
        if (str_starts_with($upper, 'KOP') || str_starts_with($upper, 'KUD') || str_contains($upper, 'KOPERASI')) {
            return 'koperasi';
        }

        return 'perorangan';
    }
}
