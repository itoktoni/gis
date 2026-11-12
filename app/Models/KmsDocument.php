<?php

namespace App\Models;

use App\Services\Kms\KmsIngestService;

class KmsDocument extends BaseModel
{
    protected $table = 'kms_documents';

    protected $primaryKey = 'kms_document_id';

    protected $fillable = [
        'kms_document_judul',
        'kms_document_deskripsi',
        'kms_document_file',
        'kms_document_mime',
        'kms_document_ukuran_kb',
        'kms_document_jumlah_halaman',
        'kms_document_jumlah_chunk',
        'kms_document_status',
        'kms_document_error',
    ];

    public static $filterColumns = [
        'kms_document_judul' => 'Judul',
        'kms_document_status' => 'Status',
    ];

    public static $sortColumns = [
        'kms_document_judul',
        'kms_document_status',
        'kms_document_jumlah_halaman',
        'kms_document_jumlah_chunk',
    ];

    protected function casts(): array
    {
        return [
            'kms_document_ukuran_kb' => 'integer',
            'kms_document_jumlah_halaman' => 'integer',
            'kms_document_jumlah_chunk' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'kms_document_judul';
    }

    public function rules(): array
    {
        return [
            'kms_document_judul' => 'required|string|max:255',
            'kms_document_deskripsi' => 'nullable|string|max:2000',
            'kms_document_file' => 'nullable|string|max:255',
            'kms_document_status' => 'nullable|string|max:20',
        ];
    }

    public function getKmsDocumentFileUrlAttribute(): string
    {
        return fileUrl($this->kms_document_file);
    }

    public function hasChunks()
    {
        return $this->hasMany(KmsChunk::class, 'kms_chunk_id_document', 'kms_document_id');
    }

    public function hasDivisis()
    {
        return $this->belongsToMany(Divisi::class, 'kms_document_divisi', 'document_id', 'divisi_id');
    }

    public function hasTags()
    {
        return $this->belongsToMany(\Modules\Cms\Models\Tag::class, 'kms_document_tag', 'document_id', 'tag_id');
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            // Ingest ulang saat file berubah; updateQuietly() di dalam
            // service mencegah rekursi event. wasRecentlyCreated karena
            // wasChanged() selalu false untuk record baru di event saved.
            $fileChanged = $model->wasRecentlyCreated || $model->wasChanged('kms_document_file');
            if ($fileChanged && ! empty($model->kms_document_file)) {
                KmsIngestService::ingest($model);
            }
        });

        static::deleting(function (self $model) {
            $file = $model->kms_document_file ?? null;
            if (! empty($file)) {
                $path = storage_path('app/public/'.$file);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        });
    }
}
