<?php

namespace App\Models;

use App\Enums\VoucherStatusEnum;

class VoucherHistory extends BaseModel
{
    protected $table = 'voucher_history';

    protected $primaryKey = 'voucher_history_id';

    protected $fillable = [
        'history_id_voucher',
        'history_status_dari',
        'history_status_ke',
        'history_catatan',
        'history_file',
        'history_id_user',
    ];

    public static $filterColumns = [];

    public static $sortColumns = [
        'history_status_ke',
        'created_at',
    ];

    public static function field_name(): string
    {
        return 'history_status_ke';
    }

    public function rules(): array
    {
        return [
            'history_id_voucher' => 'required|exists:voucher,voucher_id',
            'history_status_dari' => 'nullable|string|max:30',
            'history_status_ke' => 'required|string|max:30',
            'history_catatan' => 'nullable|string|max:500',
            'history_file' => 'nullable|string|max:255',
            'history_id_user' => 'nullable|integer',
        ];
    }

    public function hasVoucher()
    {
        return $this->belongsTo(Voucher::class, 'history_id_voucher', 'voucher_id');
    }

    public function hasUser()
    {
        return $this->belongsTo(User::class, 'history_id_user', 'id');
    }

    public function getStatusKeLabelAttribute(): string
    {
        return VoucherStatusEnum::getDescription($this->history_status_ke);
    }

    public function getStatusDariLabelAttribute(): string
    {
        return $this->history_status_dari ? VoucherStatusEnum::getDescription($this->history_status_dari) : '-';
    }
}
