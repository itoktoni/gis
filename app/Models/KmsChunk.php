<?php

namespace App\Models;

class KmsChunk extends BaseModel
{
    protected $table = 'kms_chunks';

    protected $primaryKey = 'kms_chunk_id';

    protected $fillable = [
        'kms_chunk_id_document',
        'kms_chunk_halaman',
        'kms_chunk_urutan',
        'kms_chunk_teks',
    ];

    public static $filterColumns = [
        'kms_chunk_teks' => 'Teks',
    ];

    public static $sortColumns = [
        'kms_chunk_halaman',
        'kms_chunk_urutan',
    ];

    protected function casts(): array
    {
        return [
            'kms_chunk_id_document' => 'integer',
            'kms_chunk_halaman' => 'integer',
            'kms_chunk_urutan' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'kms_chunk_teks';
    }

    public function rules(): array
    {
        return [
            'kms_chunk_id_document' => 'required|integer',
            'kms_chunk_halaman' => 'nullable|integer|min:1',
            'kms_chunk_urutan' => 'nullable|integer|min:0',
            'kms_chunk_teks' => 'required|string',
        ];
    }

    public function hasDocument()
    {
        return $this->belongsTo(KmsDocument::class, 'kms_chunk_id_document', 'kms_document_id');
    }

    /**
     * Label lokasi untuk sitasi: "Hlm. 3" atau "Bagian 2".
     */
    public function getLokasiAttribute(): string
    {
        if (! empty($this->kms_chunk_halaman)) {
            return 'Hlm. '.$this->kms_chunk_halaman;
        }

        return 'Bagian '.((int) $this->kms_chunk_urutan + 1);
    }
}
