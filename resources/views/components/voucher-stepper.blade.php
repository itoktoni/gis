@props(['status' => 'draft'])
@php
    use App\Enums\VoucherStatusEnum;
    $steps = VoucherStatusEnum::steps();
    $nos = array_map(fn($s) => VoucherStatusEnum::stepNo($s), $steps);
    $currentNo = VoucherStatusEnum::stepNo($status);
    $isBranch = $currentNo === null;
    $isFinish = ($currentNo ?? -1) === 0;
    $isRevisi = $status === VoucherStatusEnum::REVISI;
@endphp
<div {{ $attributes }}>
    @if($isRevisi)
    <p class="mb-3 inline-flex items-center gap-2 rounded-lg bg-red-100 px-3 py-2 text-xs font-bold text-red-700">
        <span class="material-symbols-outlined text-base">warning</span>
        Dikembalikan untuk revisi — perbaiki dokumen lalu ajukan ulang ke Verifikasi FA
    </p>
    @endif

    {{-- Desktop: horizontal --}}
    <ol class="hidden md:flex items-start gap-0 pb-1">
        @foreach($steps as $i => $step)
        @php
            $no = $nos[$i];
            $finish = $no === 0;
            $done = ! $isBranch && $no > 0 && ($isFinish || ($currentNo !== null && $no < $currentNo));
            $active = ! $isBranch && (($finish && $isFinish) || ($no > 0 && $no === $currentNo));
        @endphp
        <li class="flex min-w-0 flex-1 items-start">
            <div class="flex flex-col items-center gap-1 text-center w-full">
                <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold
                    {{ $done || $active && $finish ? 'bg-green-600 text-white' : ($active ? 'bg-primary text-on-primary ring-4 ring-primary/20' : ($finish ? 'bg-surface-container text-on-surface-variant' : 'bg-surface-container text-on-surface-variant')) }}">
                    @if($done || ($active && $finish))
                    <span class="material-symbols-outlined text-base">{{ $finish ? 'flag' : 'check' }}</span>
                    @elseif($finish)
                    <span class="material-symbols-outlined text-base">outlined_flag</span>
                    @else
                    {{ $no }}
                    @endif
                </span>
                <span class="text-[10px] leading-tight font-semibold {{ $active ? 'text-primary' : 'text-on-surface-variant' }}">
                    {{ VoucherStatusEnum::getDescription($step) }}
                </span>
            </div>
            @if($i < count($steps) - 1)
            <span class="mt-4 h-0.5 w-full min-w-3 rounded {{ $done ? 'bg-green-600' : 'bg-outline-variant' }}"></span>
            @endif
        </li>
        @endforeach
    </ol>

    {{-- Mobile: vertikal kompak --}}
    <ol class="md:hidden relative ml-1 space-y-0.5 before:absolute before:left-[13px] before:top-3 before:bottom-3 before:w-0.5 before:rounded before:bg-outline-variant">
        @foreach($steps as $i => $step)
        @php
            $no = $nos[$i];
            $finish = $no === 0;
            $done = ! $isBranch && $no > 0 && ($isFinish || ($currentNo !== null && $no < $currentNo));
            $active = ! $isBranch && (($finish && $isFinish) || ($no > 0 && $no === $currentNo));
        @endphp
        <li class="relative flex items-center gap-3 py-1.5 pl-9">
            <span class="absolute left-0 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-bold
                {{ $done || $active && $finish ? 'bg-green-600 text-white' : ($active ? 'bg-primary text-on-primary ring-4 ring-primary/20' : 'bg-surface-container text-on-surface-variant') }}">
                @if($done || ($active && $finish))
                <span class="material-symbols-outlined text-[16px]">{{ $finish ? 'flag' : 'check' }}</span>
                @elseif($finish)
                <span class="material-symbols-outlined text-[16px]">outlined_flag</span>
                @else
                {{ $no }}
                @endif
            </span>
            <span class="text-xs font-semibold {{ $active ? 'text-primary' : 'text-on-surface' }}">{{ VoucherStatusEnum::getDescription($step) }}</span>
            @if($active)
            <span class="ml-auto shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">posisi</span>
            @endif
        </li>
        @endforeach
    </ol>
</div>
