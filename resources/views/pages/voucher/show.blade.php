<?php /** @var App\Models\Voucher $model */ ?>
@php
    use App\Enums\VoucherStatusEnum;
    $steps = VoucherStatusEnum::steps();
    $curIdx = array_search($model->voucher_status, $steps);
    $curIdx = $curIdx === false ? 0 : $curIdx;
    $totalSteps = count($steps);
    $approveTo = VoucherStatusEnum::approveTarget($model->voucher_status ?? 'draft');
    $historyByKe = $model->hasHistory->keyBy('history_status_ke');
    $stepIcons = ['draft' => 'draft', 'verifikasi_fa' => 'fact_check', 'verifikasi_ap' => 'account_balance', 'kirim_jakarta' => 'markunread_mailbox', 'siap_bayar' => 'bolt', 'selesai' => 'hourglass_empty'];
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Tracking '.$model->voucher_no]]" />

    <div class="flex w-full flex-col gap-4 pb-24">
        {{-- Header card --}}
        <div class="flex flex-col gap-4 rounded-xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-center justify-end gap-2">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-tertiary-fixed px-3 py-1 text-on-tertiary-fixed shadow-sm">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-tertiary-container opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-tertiary-container"></span></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">{{ $model->status_label }}</span>
                </div>
            </div>
            <div class="flex flex-col justify-between gap-3 xl:flex-row xl:items-center">
                <div class="flex min-w-0 flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="tracking-tight text-primary text-[22px] font-semibold leading-7">Voucher #{{ $model->voucher_no }}</h1>
                        <span class="rounded bg-surface-container-high px-2 py-0.5 text-[12px] font-semibold text-on-surface-variant">{{ $model->voucher_estate ?? $model->hasKebun?->kebun_nama ?? '-' }}</span>
                    </div>
                    <p class="flex flex-wrap items-center gap-1 text-[14px] text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px] text-primary">domain</span>
                        Vendor: <span class="font-semibold text-on-surface">{{ $model->hasVendor?->vendor_nama ?? '-' }}</span>
                        @if($model->voucher_no_invoice)<span>• Ref: {{ $model->voucher_no_invoice }}</span>@endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="window.print()" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container-high px-3 py-2.5 text-[12px] font-semibold text-primary shadow-sm transition-all hover:bg-surface-container-highest"><span class="material-symbols-outlined text-[18px]">print</span><span>Cetak (PDF)</span></button>
                    <a href="#alur" class="inline-flex items-center gap-1.5 rounded-lg bg-surface-container-high px-3 py-2.5 text-[12px] font-semibold shadow-sm transition-all hover:bg-surface-container-highest"><span class="material-symbols-outlined text-[18px]">history</span><span>Audit Trail</span></a>
                    @if($model->voucher_status !== 'selesai')
                    @can('status', $model)
                    <a href="#formTolak" class="inline-flex items-center gap-1.5 rounded-lg bg-error-container px-3 py-2.5 text-[12px] font-semibold text-on-error-container transition-all hover:bg-error-container/80"><span class="material-symbols-outlined text-[18px]">undo</span><span>Kembalikan</span></a>
                    @if($approveTo)
                    <form method="POST" action="{{ moduleRoute('postStatus', ['id' => $model->field_primary]) }}" class="inline-flex">
                        @csrf
                        <input type="hidden" name="status_ke" value="{{ $approveTo }}">
                        <input type="hidden" name="catatan" value="Disetujui, lanjut ke {{ VoucherStatusEnum::getDescription($approveTo) }}.">
                        <button onclick="return confirm('Setujui dan lanjut ke {{ VoucherStatusEnum::getDescription($approveTo) }}?')" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-container px-4 py-2.5 text-[12px] font-semibold text-on-primary shadow-md transition-all hover:bg-primary"><span class="material-symbols-outlined text-[20px]">payments</span><span>Otorisasi &amp; Eksekusi</span></button>
                    </form>
                    @endif
                    @endcan
                    @endif
                </div>
            </div>
        </div>

        {{-- Master-detail 60/40 --}}
        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-12">
            {{-- LEFT 7 --}}
            <div class="flex flex-col gap-4 lg:col-span-7">
                <div class="flex flex-col gap-4 rounded-xl bg-surface-container-lowest p-4 shadow-sm sm:p-5" id="alur">
                    <div class="flex items-center justify-between pb-1">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-secondary">Status Progres Validasi</span>
                            <h2 class="text-[18px] font-semibold leading-6">Alur Pemeriksaan &amp; Kliring</h2>
                        </div>
                        <div class="flex items-center gap-1 rounded-lg bg-surface-container px-2 py-1 text-[12px] font-semibold text-primary"><span class="material-symbols-outlined text-[18px]">alt_route</span><span>Tahap {{ $curIdx + 1 }} dari {{ $totalSteps }}</span></div>
                    </div>
                    <div class="relative flex flex-col">
                        @foreach($steps as $idx => $code)
                        @php
                            $h = $historyByKe->get($code);
                            $done = $idx < $curIdx || $model->voucher_status === 'selesai';
                            $active = $code === $model->voucher_status && $model->voucher_status !== 'selesai';
                            $icon = $stepIcons[$code] ?? 'circle';
                            $no = VoucherStatusEnum::stepNo($code) ?? ($idx + 1);
                        @endphp
                        <div class="relative flex @if(!$loop->last) pb-4 @endif">
                            <div class="grow rounded-lg p-3 shadow-sm @if($active) bg-surface-container shadow-md @elseif($done) bg-surface-container-low @else bg-surface-container-lowest opacity-85 @endif flex flex-col gap-1">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <span class="text-[14px] font-semibold @if($done || $active) text-primary font-bold @endif">{{ $idx + 1 }}. {{ VoucherStatusEnum::getDescription($code) }}
                                        @if($active)<span class="ml-1 rounded-full bg-secondary-container px-2 py-0.5 text-[10px] font-bold uppercase text-on-secondary-container">Aktif</span>@endif
                                    </span>
                                    <span class="flex items-center gap-1 text-[12px] text-on-surface-variant"><span class="material-symbols-outlined text-[14px]">schedule</span>{{ $h?->created_at?->format('d M Y, H:i') ?? ($active ? 'Berjalan' : 'Menunggu') }}</span>
                                </div>
                                <p class="text-[12px]">Oleh: <strong class="@if($done || $active) text-primary @endif">{{ $h?->hasUser?->name ?? '—' }}</strong></p>
                                @if($h?->history_catatan)
                                <div class="mt-1 flex items-center gap-1.5 rounded bg-surface-container-lowest px-2 py-1 text-[12px] text-on-surface-variant"><span class="material-symbols-outlined text-[16px] text-tertiary-container">verified</span><span>{{ $h->history_catatan }}</span></div>
                                @elseif($active)
                                <div class="mt-1 rounded-lg bg-surface-container-lowest p-2 text-[12px] text-on-surface-variant">Menunggu aksi pada tahap ini.</div>
                                @endif
                                @if($h?->history_file)
                                <a href="{{ fileUrl($h->history_file) }}" target="_blank" class="mt-1 inline-flex w-fit items-center gap-1 text-[12px] font-semibold text-primary hover:underline"><span class="material-symbols-outlined text-[15px]">attach_file</span> Lampiran</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-lowest p-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container text-primary"><span class="material-symbols-outlined text-[22px]">notification_important</span></div>
                        <div class="flex flex-col"><span class="text-[12px] font-semibold">SLA Pelunasan: {{ $model->voucher_status === 'selesai' ? 'Selesai' : 'Berjalan' }}</span><span class="text-[12px] text-on-surface-variant">{{ $model->hasHistory->count() }} aktivitas tercatat • update {{ $model->updated_at?->format('d M Y H:i') }}</span></div>
                    </div>
                    <span class="rounded bg-tertiary-fixed px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-on-tertiary-fixed">{{ $model->voucher_status === 'revisi' ? 'Perlu Aksi' : 'Terkendali' }}</span>
                </div>

                {{-- Form tolak + bayar --}}
                @if($model->voucher_status !== 'selesai')
                @can('status', $model)
                <div class="rounded-xl bg-surface-container-lowest p-4 shadow-sm" id="formTolak">
                    <h3 class="mb-2 flex items-center gap-1.5 text-[14px] font-semibold text-error"><span class="material-symbols-outlined text-[18px]">undo</span> Tolak / Kembalikan ke Draf</h3>
                    <form method="POST" action="{{ moduleRoute('postStatus', ['id' => $model->field_primary]) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="hidden" name="status_ke" value="revisi">
                        <textarea name="catatan" rows="3" required maxlength="500" class="w-full rounded-lg border border-outline-variant bg-surface px-3 py-2 text-[14px] outline-none focus:border-error" placeholder="Alasan penolakan (wajib)..."></textarea>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="w-full text-[12px] file:mr-2 file:rounded-lg file:border-0 file:bg-error/10 file:px-3 file:py-1.5 file:text-[12px] file:font-semibold file:text-error">
                            <button onclick="return confirm('Tolak dan kembalikan?')" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-error px-5 text-[12px] font-semibold text-white"><span class="material-symbols-outlined text-[18px]">cancel</span> Kirim</button>
                        </div>
                    </form>
                </div>
                @endcan
                @can('pay', $model)
                <div class="rounded-xl bg-surface-container-lowest p-4 shadow-sm">
                    <h3 class="mb-2 flex items-center gap-1.5 text-[14px] font-semibold text-primary"><span class="material-symbols-outlined text-[18px]">payments</span> Pembayaran Vendor</h3>
                    <form method="POST" action="{{ moduleRoute('postPay', ['id' => $model->field_primary]) }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        @csrf
                        <input type="date" name="tanggal_bayar" value="{{ now()->toDateString() }}" class="h-11 rounded-lg border border-outline-variant bg-surface px-3 text-[14px] outline-none" required>
                        <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[12px] file:mr-2 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-[12px] file:font-semibold file:text-primary" required>
                        <button class="inline-flex h-11 shrink-0 items-center gap-2 rounded-lg bg-primary px-5 text-[12px] font-semibold text-white"><span class="material-symbols-outlined text-[18px]">payments</span> Bayar</button>
                    </form>
                </div>
                @endcan
                @endif
            </div>

            {{-- RIGHT 5 --}}
            <div class="flex flex-col gap-4 lg:col-span-5">
                <div class="flex flex-col gap-3 rounded-xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Rincian Keuangan &amp; AP</span><span class="material-symbols-outlined text-[20px] text-primary">receipt</span></div>
                    <div class="flex flex-col gap-1 rounded-xl bg-surface-container-low p-3">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Nilai Bersih Siap Bayar (Net)</span>
                        <div class="text-[32px] font-bold leading-10 tracking-tight text-primary">Rp {{ number_format($model->voucher_nilai, 0, ',', '.') }}</div>
                        <span class="flex items-center gap-1 text-[12px] font-semibold text-tertiary"><span class="material-symbols-outlined text-[16px]">check_circle</span>{{ $model->voucher_status === 'selesai' ? 'Sudah dibayar '.($model->voucher_tanggal_bayar?->format('d M Y') ?? '') : 'Langkah '.$curIdx.' • '.$model->status_label }}</span>
                    </div>
                    <div class="flex flex-col gap-2 pt-1 text-[14px]">
                        <div class="flex items-center justify-between"><span class="text-on-surface-variant">Qty</span><span class="font-semibold">{{ $model->voucher_qty ? number_format($model->voucher_qty, 2, ',', '.').' '.$model->voucher_satuan : '-' }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-on-surface-variant">No. Invoice</span><span class="font-semibold">{{ $model->voucher_no_invoice ?? '-' }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-on-surface-variant">Tanggal Voucher</span><span class="font-semibold">{{ $model->voucher_tanggal?->format('d M Y') ?? '-' }}</span></div>
                        <div class="my-1 h-px bg-surface-container-highest"></div>
                        <div class="flex items-center justify-between"><span class="text-on-surface-variant">Tanggal Bayar</span><span class="font-semibold">{{ $model->voucher_tanggal_bayar?->format('d M Y') ?? '—' }}</span></div>
                    </div>
                    <div class="flex flex-col gap-1 rounded-lg bg-surface-container p-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Vendor</span>
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded bg-surface-container-lowest text-[12px] font-bold text-primary shadow-sm">{{ strtoupper(substr($model->hasVendor?->vendor_nama ?? 'V', 0, 1)) }}</div>
                            <div class="flex min-w-0 flex-col"><span class="truncate text-[12px] font-semibold">{{ $model->hasVendor?->vendor_nama ?? '-' }}</span><span class="truncate font-mono text-[12px] text-on-surface-variant">{{ $model->hasVendor?->vendor_kode ?? '' }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 rounded-xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Lampiran Berkas</span>
                        <span class="material-symbols-outlined text-[20px] text-tertiary-container">fact_check</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        @if($model->voucher_file)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-container-low p-2 transition-colors hover:bg-surface-container">
                            <div class="flex min-w-0 items-center gap-2"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-error-container text-on-error-container"><span class="material-symbols-outlined text-[20px]">picture_as_pdf</span></div><div class="flex min-w-0 flex-col"><span class="truncate text-[12px] font-semibold">Dokumen Voucher</span><span class="text-[12px] text-on-surface-variant">File utama</span></div></div>
                            <a href="{{ $model->file_url }}" target="_blank" title="Buka" class="rounded-lg p-2 text-primary transition-colors hover:bg-surface-container-high"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                        </div>
                        @endif
                        @if($model->voucher_bukti_file)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-container-low p-2 transition-colors hover:bg-surface-container">
                            <div class="flex min-w-0 items-center gap-2"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-surface-container-highest text-primary"><span class="material-symbols-outlined text-[20px]">verified</span></div><div class="flex min-w-0 flex-col"><span class="truncate text-[12px] font-semibold">Bukti Bayar {{ $model->voucher_tanggal_bayar?->format('d M Y') }}</span><span class="text-[12px] text-on-surface-variant">Bukti transfer</span></div></div>
                            <a href="{{ $model->bukti_url }}" target="_blank" title="Buka" class="rounded-lg p-2 text-primary transition-colors hover:bg-surface-container-high"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                        </div>
                        @endif
                        @forelse($model->hasHistory->whereNotNull('history_file') as $h)
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-container-low p-2 transition-colors hover:bg-surface-container">
                            <div class="flex min-w-0 items-center gap-2"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded bg-surface-container-highest text-primary"><span class="material-symbols-outlined text-[20px]">draw</span></div><div class="flex min-w-0 flex-col"><span class="truncate text-[12px] font-semibold">Lampiran — {{ $h->status_ke_label }}</span><span class="text-[12px] text-on-surface-variant">{{ $h->created_at?->format('d M Y H:i') }}</span></div></div>
                            <a href="{{ fileUrl($h->history_file) }}" target="_blank" title="Buka" class="rounded-lg p-2 text-primary transition-colors hover:bg-surface-container-high"><span class="material-symbols-outlined text-[20px]">visibility</span></a>
                        </div>
                        @empty
                        @if(!$model->voucher_file && !$model->voucher_bukti_file)
                        <div class="rounded-lg border border-dashed border-outline-variant p-3 text-center text-[12px] text-outline">Belum ada lampiran.</div>
                        @endif
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-col gap-3 rounded-xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Audit Trail &amp; Integritas</span><span class="material-symbols-outlined text-[20px] text-tertiary-container">lock</span></div>
                    <div class="flex flex-col gap-1 rounded-lg bg-surface-container-low p-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">ID • Aktivitas • Update</span>
                        <span class="break-all font-mono text-[12px] font-semibold text-primary">#{{ $model->field_primary }} • {{ $model->hasHistory->count() }} aktivitas • {{ $model->updated_at?->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-[12px] font-semibold text-tertiary-container"><span class="material-symbols-outlined text-[18px]">verified_user</span><span>Tercatat otomatis &amp; terkunci.</span></div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
