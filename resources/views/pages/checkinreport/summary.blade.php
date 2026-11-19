<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => moduleRoute('getTable'), 'label' => 'Report Check-In'], ['url' => '', 'label' => 'Summary Absen']]" />
    <div class="content mt-4 mb-4 lg:mt-0">
        <form method="GET" action="{{ moduleRoute('getSummary') }}">
            <x-card label="Filter Summary Absen">
                <x-select col="4" name="user_id" label="User" :options="$userOptions" :default="request('user_id')" placeholder="-- Semua User --" />
                <x-input col="4" type="date" name="from" label="Dari Tanggal" :value="request('from')" />
                <x-input col="4" type="date" name="to" label="Sampai Tanggal" :value="request('to')" />
            </x-card>
            <div class="flex flex-wrap gap-2 mt-4">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-primary px-4 text-sm font-semibold text-white hover:brightness-110">
                    <span class="material-symbols-outlined text-xl">visibility</span> Tampilkan
                </button>
                <a href="{{ moduleRoute('getTable', ['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id')]) }}" wire:navigate class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">list</span> Detail
                </a>
                <a href="{{ moduleRoute('getPrint', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status')])) }}" target="_blank" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">print</span> Print
                </a>
                <a href="{{ moduleRoute('getExportExcel', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status')])) }}" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">download</span> Excel
                </a>
            </div>
        </form>

        <br>

        <x-table>
            <x-slot:head>
                <th>No</th>
                <th>Karyawan</th>
                <th>Total</th>
                <th>Valid</th>
                <th>Luar Jangkauan</th>
                <th>Palsu</th>
                <th>Hari Hadir</th>
                <th>% Valid</th>
            </x-slot:head>
            <x-slot:body>
                @forelse($summary as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="font-semibold">{{ $row['nama'] }}</div>
                        <div class="text-xs text-on-surface-variant">{{ $row['email'] }}</div>
                    </td>
                    <td class="font-semibold">{{ $row['total'] }}</td>
                    <td><x-badge type="success">{{ $row['valid'] }}</x-badge></td>
                    <td><x-badge type="warning">{{ $row['invalid'] }}</x-badge></td>
                    <td><x-badge type="error">{{ $row['fake'] }}</x-badge></td>
                    <td>{{ $row['hari_hadir'] }} hari</td>
                    <td class="font-semibold">{{ $row['persen_valid'] }}%</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-on-surface-variant">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <div class="p-3 space-y-3">
                    @forelse($summary as $row)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                        <p class="text-sm font-bold truncate mb-1">{{ $row['nama'] }}</p>
                        <p class="text-xs text-on-surface-variant mb-3">{{ $row['email'] }}</p>
                        <div class="grid grid-cols-4 gap-2 text-center text-xs mb-2">
                            <div class="rounded-lg bg-surface-container p-2"><div class="font-bold text-sm">{{ $row['total'] }}</div><div>Total</div></div>
                            <div class="rounded-lg bg-green-50 p-2"><div class="font-bold text-sm text-green-700">{{ $row['valid'] }}</div><div>Valid</div></div>
                            <div class="rounded-lg bg-amber-50 p-2"><div class="font-bold text-sm text-amber-700">{{ $row['invalid'] }}</div><div>Luar</div></div>
                            <div class="rounded-lg bg-red-50 p-2"><div class="font-bold text-sm text-red-700">{{ $row['fake'] }}</div><div>Palsu</div></div>
                        </div>
                        <p class="text-xs">Hari hadir: <span class="font-semibold">{{ $row['hari_hadir'] }} hari</span> &middot; Valid: <span class="font-semibold">{{ $row['persen_valid'] }}%</span></p>
                    </div>
                    @empty
                    <p class="text-center text-sm text-on-surface-variant">Tidak ada data.</p>
                    @endforelse
                </div>
            </x-slot:mobile>
        </x-table>
    </div>
</x-layouts::app>
