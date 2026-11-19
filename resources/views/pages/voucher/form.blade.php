<?php /** @var App\Models\Voucher $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    @if(isset($model) && $model->exists)
    <x-card label="Posisi Dokumen" :noGrid="true">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm">Langkah <strong>{{ \App\Enums\VoucherStatusEnum::stepOf($model->voucher_status) }}</strong> dari 6 — <strong>{{ $model->status_label }}</strong></span>
            <a href="{{ moduleRoute('getShow', ['id' => $model->field_primary]) }}" wire:navigate class="ml-auto inline-flex items-center gap-2 rounded-lg bg-primary text-on-primary px-4 py-2 text-sm font-semibold hover:bg-primary/90">
                <span class="material-symbols-outlined text-lg">timeline</span> Buka Tracking (Setujui / Tolak)
            </a>
        </div>
        <div class="mt-3"><x-voucher-stepper :status="$model->voucher_status" /></div>
    </x-card>
    @endif

    @if(isset($model) && $model->exists)
    @php $nextStatus = \App\Enums\VoucherStatusEnum::nextOptions($model->voucher_status ?? \App\Enums\VoucherStatusEnum::DRAFT); @endphp
    @can('status', $model)
    @if(!empty($nextStatus))
    <x-card label="Penggantian Status" :noGrid="true">
        <form method="POST" action="{{ moduleRoute('postStatus', ['id' => $model->field_primary]) }}" enctype="multipart/form-data" class="flex flex-col gap-2">
            @csrf
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 sm:col-span-4">
                    <label class="text-xs font-semibold">Status Berikutnya</label>
                    <select name="status_ke" class="mt-1 w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 text-sm" required>
                        @foreach($nextStatus as $code)
                            <option value="{{ $code }}">{{ \App\Enums\VoucherStatusEnum::getDescription($code) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-12 sm:col-span-8">
                    <label class="text-xs font-semibold">Catatan (wajib bila Revisi/Ditolak)</label>
                    <input type="text" name="catatan" maxlength="500" placeholder="Catatan perpindahan status..." class="mt-1 w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 text-sm">
                </div>
                <div class="col-span-12">
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="w-full text-xs">
                </div>
            </div>
            <div>
                <button onclick="return confirm('Ganti status dokumen?')" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                    <span class="material-symbols-outlined text-lg">sync</span> Ganti Status
                </button>
            </div>
        </form>
    </x-card>
    @endif
    @endcan
    @endif

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="voucher_no" label="No Voucher" />
                <x-select col="4" name="voucher_id_vendor" label="Vendor" :options="$vendorOptions" class="search" />
                <x-input col="4" name="voucher_tanggal" label="Tanggal Dokumen" type="date" />

                <x-select col="4" name="voucher_id_kebun" label="Kebun (opsional)" :options="$kebunOptions" class="search" />
                <x-input col="4" name="voucher_estate" label="Estate" />
                <x-input col="4" name="voucher_no_invoice" label="No Invoice" />

                <x-input col="12" name="voucher_redaksi" label="Redaksi / Uraian" />

                <x-input col="4" name="voucher_qty" label="Quantity" type="number" />
                <x-input col="4" name="voucher_satuan" label="Satuan" />
                <x-input col="4" name="voucher_nilai" label="Nilai Voucher (Rp)" type="number" />

                @if(isset($model) && $model->voucher_file)
                <div class="col-span-12">
                    <p class="text-sm">Dokumen saat ini: <a href="{{ $model->file_url }}" target="_blank" class="text-primary hover:underline font-medium">{{ basename($model->voucher_file) }}</a></p>
                    <label class="inline-flex items-center gap-1.5 mt-1 text-xs text-on-surface-variant cursor-pointer">
                        <input type="checkbox" name="remove_voucher_file" value="1" class="rounded border-outline-variant text-error focus:ring-error"> Hapus file saat ini
                    </label>
                </div>
                @endif
                <x-file name="voucher_file" label="Dokumen Pendukung" col="12" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png" helper="PDF/DOCX/XLSX/JPG/PNG, maks 10MB" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
