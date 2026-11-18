<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SignatureString extends BaseModel
{
    use HasUuids;

    protected $table = 'signature_string';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['signature_id', 'original_string'];

    public static function field_name(): string
    {
        return 'original_string';
    }
}
