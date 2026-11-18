<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Checkin extends BaseModel
{
    use HasUuids;

    protected $table = 'checkin';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'signature_id', 'user_id', 'latitude', 'longitude',
        'accuracy', 'selisih_radius_m', 'address', 'client_timestamp', 'photo_path',
        'status', 'failure_reason', 'payload_json', 'ip_address',
        'original_latitude', 'original_longitude', 'original_source', 'original_accuracy',
        'original_distance_m', 'original_address', 'original_map_url',
        'is_offline',
    ];

    public static $filterColumns = [
        'status' => 'Status',
        'user_id' => 'User',
    ];

    public static $sortColumns = [
        'created_at',
        'status',
        'address',
        'selisih_radius_m',
    ];

    protected function casts(): array
    {
        return ['client_timestamp' => 'datetime', 'is_offline' => 'boolean'];
    }

    public static function field_name(): string
    {
        return 'address';
    }

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function hasSignature()
    {
        return $this->belongsTo(Signature::class, 'signature_id', 'id');
    }

    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }
}
