<?php

namespace App\Models;

use App\Enums\VoucherStatusEnum;
use Illuminate\Validation\Rule;

class Voucher extends BaseModel
{
    protected $table = 'voucher';

    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'voucher_no',
        'voucher_id_vendor',
        'voucher_id_kebun',
        'voucher_estate',
        'voucher_no_invoice',
        'voucher_redaksi',
        'voucher_qty',
        'voucher_satuan',
        'voucher_nilai',
        'voucher_tanggal',
        'voucher_status',
        'voucher_file',
        'voucher_tanggal_bayar',
        'voucher_bukti_file',
    ];

    public static $filterColumns = [
        'voucher_no' => 'No Voucher',
        'voucher_id_vendor' => 'Vendor',
        'voucher_status' => 'Status',
        'voucher_estate' => 'Estate',
    ];

    public static $sortColumns = [
        'voucher_no',
        'voucher_id_vendor',
        'voucher_estate',
        'voucher_nilai',
        'voucher_tanggal',
        'voucher_status',
    ];

    protected function casts(): array
    {
        return [
            'voucher_tanggal' => 'date',
            'voucher_tanggal_bayar' => 'date',
            'voucher_qty' => 'float',
            'voucher_nilai' => 'float',
        ];
    }

    public static function field_name(): string
    {
        return 'voucher_no';
    }

    public function rules(): array
    {
        $ignore = request()->route('id');

        return [
            'voucher_no' => 'required|string|max:100|unique:voucher,voucher_no,'.$ignore.',voucher_id',
            'voucher_id_vendor' => 'required|exists:vendor,vendor_id',
            'voucher_id_kebun' => 'nullable|exists:kebun,kebun_id',
            'voucher_estate' => 'nullable|string|max:100',
            'voucher_no_invoice' => 'nullable|string|max:100',
            'voucher_redaksi' => 'nullable|string|max:500',
            'voucher_qty' => 'nullable|numeric|min:0',
            'voucher_satuan' => 'nullable|string|max:20',
            'voucher_nilai' => 'required|numeric|min:0',
            'voucher_tanggal' => 'nullable|date',
            'voucher_status' => ['nullable', 'string', Rule::in(VoucherStatusEnum::getValues())],
            'voucher_file' => 'nullable|string|max:255',
            'voucher_tanggal_bayar' => 'nullable|date',
            'voucher_bukti_file' => 'nullable|string|max:255',
        ];
    }

    public function hasVendor()
    {
        return $this->belongsTo(Vendor::class, 'voucher_id_vendor', 'vendor_id');
    }

    public function hasKebun()
    {
        return $this->belongsTo(Kebun::class, 'voucher_id_kebun', 'kebun_id');
    }

    public function hasHistory()
    {
        return $this->hasMany(VoucherHistory::class, 'history_id_voucher', 'voucher_id')->orderByDesc('created_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return VoucherStatusEnum::getDescription($this->voucher_status);
    }

    public function getFileUrlAttribute(): string
    {
        return fileUrl($this->voucher_file);
    }

    public function getBuktiUrlAttribute(): string
    {
        return fileUrl($this->voucher_bukti_file);
    }
}
