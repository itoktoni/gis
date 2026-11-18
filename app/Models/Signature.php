<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Signature extends BaseModel
{
    use HasUuids;

    protected $table = 'absen';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'crypto_key_id', 'type', 'label', 'original_filename',
        'content_hash', 'embedded_signature', 'status', 'expired_at',
        'mock', 'developer', 'alamat', 'latitude', 'longitude', 'photo', 'accuracy',
        'jarak_m', 'ip_address', 'client_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'mock' => 'boolean',
            'developer' => 'boolean',
            'client_timestamp' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public static $filterColumns = [
        'type' => 'Tipe',
        'status' => 'Status',
    ];

    public static $sortColumns = [
        'created_at',
        'type',
        'status',
        'original_filename',
    ];

    public static function field_name(): string
    {
        return 'original_filename';
    }

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** Alias kompatibilitas service tanda tangan. */
    public function user()
    {
        return $this->hasUser();
    }

    public function logs()
    {
        return $this->hasMany(ValidationLog::class, 'signature_id', 'id');
    }

    public function stringData()
    {
        return $this->hasOne(SignatureString::class, 'signature_id', 'id');
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }
}
