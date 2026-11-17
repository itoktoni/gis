<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Report Cuti']]" />
    <x-card :label="'Filter Report Cuti'">
        <form method="GET" action="{{ moduleRoute('getPrint') }}" target="_blank">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-3"><label>Dari</label><input type="date" name="dari" class="input" value="{{ request('dari') }}" /></div>
                <div class="col-span-3"><label>Sampai</label><input type="date" name="sampai" class="input" value="{{ request('sampai') }}" /></div>
                <div class="col-span-3"><label>Karyawan</label><select name="cuti_id_user" class="input"><option value="">Semua</option>@foreach($userOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                <div class="col-span-3"><label>Status</label><select name="cuti_status" class="input"><option value="">Semua</option><option value="pending">Menunggu</option><option value="approved">Disetujui</option><option value="rejected">Ditolak</option></select></div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn btn-primary">Tampilkan / Print</button>
                <button type="submit" formaction="{{ moduleRoute('getExportExcel') }}" class="btn">Export Excel</button>
            </div>
        </form>
    </x-card>
</x-layouts::app>
