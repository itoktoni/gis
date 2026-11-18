<?php

namespace App\Models;

class ValidationLog extends BaseModel
{
    protected $table = 'validation_log';

    protected $fillable = ['signature_id', 'result', 'ip', 'user_agent'];

    public function signature()
    {
        return $this->belongsTo(Signature::class, 'signature_id', 'id');
    }

    public static function field_name(): string
    {
        return 'result';
    }
}
