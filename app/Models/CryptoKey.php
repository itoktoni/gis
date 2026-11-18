<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CryptoKey extends BaseModel
{
    use HasUuids;

    protected $table = 'crypto_key';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'public_key', 'encrypted_private_key', 'fingerprint', 'is_active'];

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** Alias kompatibilitas service tanda tangan. */
    public function user()
    {
        return $this->hasUser();
    }

    public static function field_name(): string
    {
        return 'id';
    }
}
