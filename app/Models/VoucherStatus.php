<?php

namespace App\Models;

class VoucherStatus extends BaseModel
{
    protected $table = 'voucher_status';

    protected $primaryKey = 'voucher_status_id';

    protected $fillable = [
        'voucher_status_kode',
        'voucher_status_nama',
        'voucher_status_no',
        'voucher_status_keterangan',
    ];

    public static $filterColumns = [
        'voucher_status_kode' => 'Kode',
        'voucher_status_nama' => 'Nama Status',
    ];

    public static $sortColumns = [
        'voucher_status_no',
        'voucher_status_kode',
        'voucher_status_nama',
    ];

    protected function casts(): array
    {
        return [
            'voucher_status_no' => 'integer',
        ];
    }

    public static function field_name(): string
    {
        return 'voucher_status_nama';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'voucher_status_kode' => 'required|string|max:30|unique:voucher_status,voucher_status_kode,'.$ignore.',voucher_status_id',
            'voucher_status_nama' => 'required|string|max:255',
            'voucher_status_no' => 'nullable|integer',
            'voucher_status_keterangan' => 'nullable|string|max:255',
        ];
    }

    /** Langkah maju terurut (no > 0); 0 = finish; NULL = cabang (revisi). */
    public static function orderedSteps()
    {
        return static::whereNotNull('voucher_status_no')
            ->where('voucher_status_no', '>', 0)
            ->orderBy('voucher_status_no')
            ->get();
    }

    public static function finishRow()
    {
        return static::where('voucher_status_no', 0)->first();
    }
}
