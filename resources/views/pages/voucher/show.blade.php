<?php /** @var App\Models\Voucher $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Tracking '.$model->voucher_no]]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Header dokumen --}}
        <x-card :label="'Tracking — '.$model->voucher_no" :noGrid="true">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                    <span class="material-symbols-outlined text-sm">receipt_long</span>
                    {{ $model->voucher_no }}
                </span>
                <span class="inline-flex items-center rounded-full bg-surface-container px-3 py-1 text-xs font-semibold text-on-surface-variant">
                    Langkah {{ \App\Enums\VoucherStatusEnum::stepNo($model->voucher_status) ?? '–' }} • {{ $model->status_label }}
                </span>
                <span class="inline-flex items-center rounded-full bg-surface-container px-3 py-1 text-xs font-semibold text-on-surface-variant">
                    {{ $model->voucher_tanggal?->format('d M Y') ?? '-' }}
                </span>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <dt class="text-[10px] uppercase tracking-wide text-on-surface-variant">Vendor</dt>
                    <dd class="text-sm font-bold break-words">{{ $model->hasVendor?->vendor_nama ?? '-' }}</dd>
                    <dd class="text-[11px] text-on-surface-variant font-mono">{{ $model->hasVendor?->vendor_kode ?? '' }}</dd>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <dt class="text-[10px] uppercase tracking-wide text-on-surface-variant">Nilai</dt>
                    <dd class="text-lg font-bold text-primary break-words">Rp {{ number_format($model->voucher_nilai, 0, ',', '.') }}</dd>
                    <dd class="text-[11px] text-on-surface-variant">{{ $model->voucher_qty ? number_format($model->voucher_qty, 2, ',', '.').' '.$model->voucher_satuan : '-' }}{{ $model->voucher_no_invoice ? ' • '.$model->voucher_no_invoice : '' }}</dd>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <dt class="text-[10px] uppercase tracking-wide text-on-surface-variant">Estate / Kebun</dt>
                    <dd class="text-sm font-bold break-words">{{ $model->voucher_estate ?? $model->hasKebun?->kebun_nama ?? '-' }}</dd>
                    <dd class="text-[11px] text-on-surface-variant">{{ $model->hasKebun?->hasPt ? $model->hasKebun->hasPt->pt_kode.' - '.$model->hasKebun->hasPt->pt_nama : '' }}</dd>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <dt class="text-[10px] uppercase tracking-wide text-on-surface-variant">Dokumen</dt>
                    <dd class="flex flex-col gap-1">
                        @if($model->voucher_file)
                        <a href="{{ $model->file_url }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"><span class="material-symbols-outlined text-base">description</span> Lihat file</a>
                        @else
                        <span class="text-sm text-on-surface-variant">Belum ada</span>
                        @endif
                        @if($model->voucher_bukti_file)
                        <a href="{{ $model->bukti_url }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-success hover:underline"><span class="material-symbols-outlined text-base">payments</span> Bukti bayar</a>
                        <span class="text-[11px] text-on-surface-variant">{{ $model->voucher_tanggal_bayar?->format('d M Y') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @if($model->voucher_redaksi)
            <p class="text-sm text-on-surface-variant border-t border-outline-variant pt-3 mt-4">{{ $model->voucher_redaksi }}</p>
            @endif
        </x-card>

        {{-- Stepper status bernomor --}}
        <x-card label="Alur Status" :noGrid="true">
            <x-voucher-stepper :status="$model->voucher_status" />
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Setujui / Tolak --}}
            <x-card label="Keputusan" :noGrid="true">
                @php $approveTo = \App\Enums\VoucherStatusEnum::approveTarget($model->voucher_status ?? 'draft'); @endphp
                @if($model->voucher_status === 'selesai')
                <p class="text-sm text-success font-semibold">Dokumen sudah selesai dibayar. Tidak ada aksi lagi.</p>
                @else
                @can('status', $model)
                @if($approveTo)
                <form method="POST" action="{{ moduleRoute('postStatus', ['id' => $model->field_primary]) }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="status_ke" value="{{ $approveTo }}">
                    <input type="hidden" name="catatan" value="Disetujui, lanjut ke {{ \App\Enums\VoucherStatusEnum::getDescription($approveTo) }}.">
                    <button type="submit" onclick="return confirm('Setujui dan lanjut ke {{ \App\Enums\VoucherStatusEnum::getDescription($approveTo) }}?')" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 text-white px-5 h-11 text-sm font-semibold hover:bg-green-700">
                        <span class="material-symbols-outlined">check_circle</span>
                        Setujui → {{ \App\Enums\VoucherStatusEnum::getDescription($approveTo) }}
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ moduleRoute('postStatus', ['id' => $model->field_primary]) }}" enctype="multipart/form-data" class="space-y-3 border-t border-outline-variant pt-4">
                    @csrf
                    <input type="hidden" name="status_ke" value="revisi">
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Alasan penolakan (wajib)</label>
                        <textarea name="catatan" rows="3" required maxlength="500" class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm outline-none focus:border-error" placeholder="Contoh: nominal tidak sesuai invoice, mohon perbaiki..."></textarea>
                    </div>
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Lampiran (opsional)</label>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-error/10 file:text-error hover:file:bg-error/20 file:cursor-pointer">
                    </div>
                    <button type="submit" onclick="return confirm('Tolak dokumen dan kembalikan untuk revisi?')" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-error text-on-error px-5 h-11 text-sm font-semibold hover:bg-error/90">
                        <span class="material-symbols-outlined">cancel</span> Tolak / Minta Revisi
                    </button>
                </form>
                @else
                <p class="text-sm text-on-surface-variant">Tidak ada akses keputusan.</p>
                @endcan
                @endif
            </x-card>

            {{-- Pembayaran --}}
            <x-card label="Pembayaran Vendor" :noGrid="true">
                @if($model->voucher_status === 'selesai')
                <p class="text-sm text-success font-semibold">Sudah dibayar {{ $model->voucher_tanggal_bayar?->format('d M Y') }}.</p>
                @else
                @can('pay', $model)
                <form method="POST" action="{{ moduleRoute('postPay', ['id' => $model->field_primary]) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Tanggal Bayar</label>
                        <input type="date" name="tanggal_bayar" value="{{ now()->toDateString() }}" class="w-full h-12 px-4 bg-white border border-outline-variant rounded-lg text-sm outline-none" required>
                    </div>
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Bukti Transfer (wajib)</label>
                        <input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer" required>
                        <p class="text-[11px] text-on-surface-variant mt-1">PDF/JPG/PNG bukti transfer, maks 10MB. Status otomatis jadi Selesai.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 text-white px-5 h-11 text-sm font-semibold hover:bg-green-700">
                        <span class="material-symbols-outlined">payments</span> Tandai Dibayar
                    </button>
                </form>
                @else
                <p class="text-sm text-on-surface-variant">Tidak ada akses pembayaran.</p>
                @endcan
                @endif
            </x-card>
        </div>

        {{-- History --}}
        <x-card label="History Dokumen" :noGrid="true">
            @if($model->hasHistory->isEmpty())
            <div class="text-center py-8 text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                <p class="text-sm">Belum ada history.</p>
            </div>
            @else
            @php
                $dot = [
                    'revisi' => 'bg-error',
                    'selesai' => 'bg-green-600',
                    'siap_bayar' => 'bg-green-500',
                    'kirim_jakarta' => 'bg-violet-500',
                    'verifikasi_ap' => 'bg-blue-500',
                    'verifikasi_fa' => 'bg-amber-500',
                    'draft' => 'bg-surface-container',
                ];
                $badge = [
                    'revisi' => 'bg-red-100 text-red-700',
                    'selesai' => 'bg-green-600 text-white',
                    'siap_bayar' => 'bg-green-100 text-green-700',
                    'kirim_jakarta' => 'bg-violet-100 text-violet-700',
                    'verifikasi_ap' => 'bg-blue-100 text-blue-700',
                    'verifikasi_fa' => 'bg-amber-100 text-amber-700',
                    'draft' => 'bg-surface-container text-on-surface-variant',
                ];
            @endphp
            <ol class="relative ml-2 space-y-3 before:absolute before:left-[9px] before:top-3 before:bottom-3 before:w-0.5 before:rounded before:bg-outline-variant">
                @foreach($model->hasHistory as $h)
                <li class="relative pl-9">
                    <span class="absolute left-0 top-1 flex w-5 h-5 items-center justify-center rounded-full {{ $dot[$h->history_status_ke] ?? 'bg-primary' }} ring-4 ring-surface-container-lowest">
                        <span class="material-symbols-outlined text-[12px] text-white">
                            {{ $h->history_status_ke === 'revisi' ? 'close' : ($h->history_status_ke === 'selesai' ? 'check' : 'arrow_forward') }}
                        </span>
                    </span>
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-surface-container text-on-surface-variant">{{ $h->status_dari_label }}</span>
                            <span class="material-symbols-outlined text-sm text-on-surface-variant">arrow_right_alt</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge[$h->history_status_ke] ?? $badge['draft'] }}">{{ $h->status_ke_label }}</span>
                        </div>
                        <p class="mt-1.5 flex items-center gap-1 text-[11px] text-on-surface-variant">
                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                            {{ $h->created_at?->format('d M Y H:i') }}
                            <span>•</span>
                            <span class="material-symbols-outlined text-[14px]">person</span>
                            {{ $h->hasUser?->name ?? 'System' }}
                        </p>
                        @if($h->history_catatan)
                        <p class="mt-2 rounded-lg bg-surface-container px-3 py-2 text-[13px] text-on-surface">{{ $h->history_catatan }}</p>
                        @endif
                        @if($h->history_file)
                        <a href="{{ fileUrl($h->history_file) }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                            <span class="material-symbols-outlined text-[16px]">attach_file</span> Lihat lampiran
                        </a>
                        @endif
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </x-card>

        <div class="h-16"></div>
    </div>
</x-layouts::app>
