<?php /** @var App\Models\Cuti $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Approve Cuti']]" />

    <x-card :label="'Approve Cuti — ' . ($model->hasUser?->name ?? '-')">
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div><p class="text-xs">Jenis</p><p class="font-bold">{{ $model->cuti_jenis }}</p></div>
            <div><p class="text-xs">Periode</p><p class="font-bold">{{ $model->cuti_tanggal_mulai?->format('d/m/Y') }} s/d {{ $model->cuti_tanggal_selesai?->format('d/m/Y') }} ({{ $model->cuti_jumlah_hari }} hari)</p></div>
            <div><p class="text-xs">Keperluan</p><p>{{ $model->cuti_keperluan }}</p></div>
            <div><p class="text-xs">Sisa cuti tahunan ybs</p><p class="font-bold">{{ $sisa ?? '-' }} hari</p></div>
            <div><p class="text-xs">Status saat ini</p><p class="font-bold">{{ $model->cuti_status }}</p></div>
        </div>

        <form method="POST" action="{{ moduleRoute('postApprove', ['id' => $model->field_primary]) }}">
            @csrf
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-6">
                    <label>Keputusan</label>
                    <select name="cuti_status" class="input">
                        <option value="approved" {{ $model->cuti_status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ $model->cuti_status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-span-12">
                    <label>Catatan approver</label>
                    <textarea name="cuti_catatan" class="input" rows="3">{{ old('cuti_catatan', $model->cuti_catatan) }}</textarea>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
                <a href="{{ moduleRoute('getDownload', ['id' => $model->field_primary]) }}" class="btn">Download PDF</a>
                <a href="{{ moduleRoute('getTable') }}" class="btn">Kembali</a>
            </div>
        </form>
    </x-card>
</x-layouts::app>
