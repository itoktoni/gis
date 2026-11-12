<?php

namespace App\Http\Controllers;

use App\Actions\CreateAction;
use App\Actions\UpdateAction;
use App\Concerns\ControllerTrait;
use App\Enums\KmsDocumentStatusEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\Divisi;
use App\Models\KmsDocument;
use App\Services\Kms\KmsChatService;
use Illuminate\Validation\ValidationException;
use Modules\Cms\Models\Tag;
use Throwable;

class KmsDocumentController extends Controller
{
    use ControllerTrait {
        postCreate as protected traitPostCreate;
        postUpdate as protected traitPostUpdate;
    }

    public function __construct(KmsDocument $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'statusOptions' => KmsDocumentStatusEnum::getOptions(),
            'divisiOptions' => Divisi::getCodeOptions(),
            'tagOptions' => Tag::orderBy('name')->get()->pluck('name', 'id'),
        ];

        return array_merge($default, $data);
    }

    public function postCreate(GeneralRequest $request)
    {
        $this->validateRelations($request);
        $this->mergeDocumentFile($request, null);

        $response = CreateAction::run($request, $this->model);
        $this->syncRelations($response, $request);

        return $this->response($response);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $document = $this->model->findOrFail($id);
        $existing = $document->kms_document_file ?? null;

        $this->validateRelations($request);
        $this->mergeDocumentFile($request, $existing);

        $response = UpdateAction::run($request, $id, $this->model);
        if ($response['status']) {
            $document->refresh();
            $document->hasDivisis()->sync($request->input('divisi_ids', []));
            $document->hasTags()->sync($request->input('tag_ids', []));
        }

        return $this->response($response);
    }

    public function getChat(GeneralRequest $request)
    {
        return $this->views($this->template(), [
            'pertanyaan' => null,
            'hasil' => null,
            'filterDivisi' => [],
            'filterTag' => [],
        ]);
    }

    public function postChat(GeneralRequest $request)
    {
        $data = $request->validate([
            'pertanyaan' => 'required|string|max:2000',
            'divisi_ids' => 'nullable|array',
            'divisi_ids.*' => 'exists:divisi,divisi_id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:cms_tags,id',
        ]);

        $divisiIds = array_values($data['divisi_ids'] ?? []);
        $tagIds = array_values($data['tag_ids'] ?? []);

        try {
            $hasil = KmsChatService::ask($data['pertanyaan'], null, $divisiIds, $tagIds);
        } catch (Throwable $e) {
            $hasil = [
                'jawaban' => 'Gagal memproses pertanyaan: '.$e->getMessage(),
                'sumber' => [],
            ];
        }

        return $this->views($this->template('chat'), [
            'pertanyaan' => $data['pertanyaan'],
            'hasil' => $hasil,
            'filterDivisi' => $divisiIds,
            'filterTag' => $tagIds,
        ]);
    }

    public function getDownload(GeneralRequest $request, $id)
    {
        $document = $this->model->findOrFail($id);
        $relative = $document->kms_document_file ?? null;

        if (empty($relative)) {
            abort(404, 'Dokumen belum punya file.');
        }

        $absolute = storage_path('app/public/'.$relative);
        if (! is_file($absolute)) {
            abort(404, 'File tidak ditemukan di penyimpanan.');
        }

        return response()->download($absolute, $document->kms_document_judul.'.'.pathinfo($relative, PATHINFO_EXTENSION));
    }

    private function validateRelations(GeneralRequest $request): void
    {
        $request->validate([
            'divisi_ids' => 'nullable|array',
            'divisi_ids.*' => 'exists:divisi,divisi_id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:cms_tags,id',
        ]);
    }

    private function syncRelations(array $response, GeneralRequest $request): void
    {
        if (! ($response['status'] ?? false) || ! ($response['data'] instanceof KmsDocument)) {
            return;
        }

        $response['data']->hasDivisis()->sync($request->input('divisi_ids', []));
        $response['data']->hasTags()->sync($request->input('tag_ids', []));
    }

    private function mergeDocumentFile(GeneralRequest $request, ?string $existing): void
    {
        if ($request->hasFile('kms_document_file')) {
            try {
                $path = uploadDocument($request->file('kms_document_file'), 'kms', ['max_size' => 10240]);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['kms_document_file' => $e->getMessage()]);
            }

            $this->deleteDocumentFile($existing);
            $request->merge([
                'kms_document_file' => $path,
                'kms_document_mime' => $request->file('kms_document_file')->getMimeType(),
                'kms_document_ukuran_kb' => (int) ceil($request->file('kms_document_file')->getSize() / 1024),
                'kms_document_status' => KmsDocumentStatusEnum::PROCESSING,
                'kms_document_error' => null,
            ]);

            return;
        }

        if ($request->boolean('remove_kms_document_file')) {
            $this->deleteDocumentFile($existing);
            $request->merge([
                'kms_document_file' => null,
                'kms_document_mime' => null,
                'kms_document_ukuran_kb' => 0,
                'kms_document_status' => KmsDocumentStatusEnum::PROCESSING,
            ]);
        }
    }

    private function deleteDocumentFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $file = storage_path('app/public/'.$path);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
