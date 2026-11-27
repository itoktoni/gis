@php
    $title = 'Website Settings';
@endphp

<x-layouts::app :title="$title">
    <div>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-on-surface">Website Settings</h2>
        </div>

        <form method="POST" action="{{ route('settings.website.save') }}" enctype="multipart/form-data" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Website Name</label>
                    <input type="text" name="name" value="{{ old('name', $settings['name'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Tagline</label>
                    <input type="text" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-on-surface mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">{{ old('description', $settings['description'] ?? '') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-on-surface mb-1">Address</label>
                    <textarea name="alamat" rows="2"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">{{ old('alamat', $settings['alamat'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Phone</label>
                    <input type="text" name="telepon" value="{{ old('telepon', $settings['telepon'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $settings['email'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>

                {{-- Logo Upload --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Logo</label>
                    @php
                        $logoUrl = \App\Models\WebsiteSetting::fileUrl($settings['logo'] ?? null);
                    @endphp
                    <div class="flex items-center gap-4 mb-2">
                        <div id="logo-preview" class="shrink-0 w-16 h-16 rounded-lg border border-outline-variant bg-surface flex items-center justify-center overflow-hidden">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="max-w-full max-h-full object-contain">
                            @else
                                <span class="material-symbols-outlined text-on-surface-variant/40">image</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" id="logo-input" accept="image/*"
                                class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer"
                                onchange="previewImage(this, 'logo-preview')">
                            @if($logoUrl)
                                <label class="inline-flex items-center gap-1.5 mt-2 text-xs text-on-surface-variant cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                    Remove current logo
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('logo')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Favicon Upload --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Favicon</label>
                    @php
                        $faviconUrl = \App\Models\WebsiteSetting::fileUrl($settings['favicon'] ?? null);
                    @endphp
                    <div class="flex items-center gap-4 mb-2">
                        <div id="favicon-preview" class="shrink-0 w-10 h-10 rounded-lg border border-outline-variant bg-surface flex items-center justify-center overflow-hidden">
                            @if($faviconUrl)
                                <img src="{{ $faviconUrl }}" alt="Favicon" class="max-w-full max-h-full object-contain">
                            @else
                                <span class="material-symbols-outlined text-on-surface-variant/40 text-base">image</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" name="favicon" id="favicon-input" accept="image/*"
                                class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer"
                                onchange="previewImage(this, 'favicon-preview')">
                            @if($faviconUrl)
                                <label class="inline-flex items-center gap-1.5 mt-2 text-xs text-on-surface-variant cursor-pointer">
                                    <input type="checkbox" name="remove_favicon" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                    Remove current favicon
                                </label>
                            @endif
                        </div>
                    </div>
                    @error('favicon')
                        <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Primary Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="primary_color" value="{{ old('primary_color', $settings['colors']['primary'] ?? '#00288e') }}"
                            class="h-10 w-16 border border-outline-variant rounded-lg cursor-pointer">
                        <input type="text" id="primary_color_hex"
                            value="{{ old('primary_color', $settings['colors']['primary'] ?? '#00288e') }}"
                            class="w-28 border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm font-mono"
                            pattern="#[0-9a-fA-F]{6}" placeholder="#00288e"
                            oninput="document.querySelector('input[name=primary_color]').value = this.value">
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1">Updates all buttons, links, and accent colors.</p>
                </div>
                <div class="flex items-end">
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-outline-variant bg-surface">
                        <span class="text-sm text-on-surface-variant">Preview:</span>
                        <span class="inline-block w-8 h-8 rounded-lg" style="background-color: {{ $settings['colors']['primary'] ?? '#00288e' }}"></span>
                        <span class="px-3 py-1 rounded-lg text-xs font-semibold text-on-primary" style="background-color: {{ $settings['colors']['primary'] ?? '#00288e' }}">Primary</span>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-on-surface mb-1">Footer Text</label>
                    <textarea name="footer_text" rows="2"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">{{ old('footer_text', $settings['footer_text'] ?? '') }}</textarea>
                </div>
            </div>

            @php $report = $settings['report'] ?? []; @endphp
            <div class="mt-8 mb-4 border-t border-outline-variant pt-6">
                <h3 class="text-lg font-bold text-on-surface">Header &amp; Footer Report</h3>
                <p class="text-xs text-on-surface-variant mt-1">Upload gambar kop (header) dan footer. Tampil di setiap halaman cetak report. Filter aktif otomatis tampil sebagai sub-judul di bawah judul report.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                {{-- Gambar kiri (logo) upload --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Gambar Kiri (Logo)</label>
                    @php $headerLeftUrl = \App\Models\WebsiteSetting::fileUrl($report['header_left'] ?? null); @endphp
                    <div class="mb-2 rounded-lg border border-outline-variant bg-surface overflow-hidden">
                        @if($headerLeftUrl)
                            <img id="report-header-left-preview" src="{{ $headerLeftUrl }}" alt="Gambar kiri" class="w-full max-h-32 object-contain">
                        @else
                            <div id="report-header-left-preview" class="flex h-24 items-center justify-center text-on-surface-variant/40">
                                <span class="material-symbols-outlined">image</span>
                            </div>
                        @endif
                    </div>
                    <input type="file" name="report_header_left" accept="image/*"
                        class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer"
                        onchange="previewImage(this, 'report-header-left-preview')">
                    @if($headerLeftUrl)
                        <label class="inline-flex items-center gap-1.5 mt-2 text-xs text-on-surface-variant cursor-pointer">
                            <input type="checkbox" name="remove_report_header_left" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                            Hapus gambar
                        </label>
                    @endif
                    @error('report_header_left')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Header image upload --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Gambar Header / Kop (Kanan)</label>
                    @php $headerUrl = \App\Models\WebsiteSetting::fileUrl($report['header_image'] ?? null); @endphp
                    <div class="mb-2 rounded-lg border border-outline-variant bg-surface overflow-hidden">
                        @if($headerUrl)
                            <img id="report-header-preview" src="{{ $headerUrl }}" alt="Header report" class="w-full max-h-32 object-contain">
                        @else
                            <div id="report-header-preview" class="flex h-24 items-center justify-center text-on-surface-variant/40">
                                <span class="material-symbols-outlined">image</span>
                            </div>
                        @endif
                    </div>
                    <input type="file" name="report_header_image" accept="image/*"
                        class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer"
                        onchange="previewImage(this, 'report-header-preview')">
                    <div class="mt-2 flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-on-surface">
                            <input type="checkbox" name="report_show_header" value="1" {{ old('report_show_header', $report['show_header'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant">
                            Tampilkan header
                        </label>
                        @if($headerUrl)
                            <label class="inline-flex items-center gap-1.5 text-xs text-on-surface-variant cursor-pointer">
                                <input type="checkbox" name="remove_report_header" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                Hapus gambar
                            </label>
                        @endif
                    </div>
                    @error('report_header_image')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Footer image upload --}}
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Gambar Footer</label>
                    @php $footerUrl = \App\Models\WebsiteSetting::fileUrl($report['footer_image'] ?? null); @endphp
                    <div class="mb-2 rounded-lg border border-outline-variant bg-surface overflow-hidden">
                        @if($footerUrl)
                            <img id="report-footer-preview" src="{{ $footerUrl }}" alt="Footer report" class="w-full max-h-32 object-contain">
                        @else
                            <div id="report-footer-preview" class="flex h-24 items-center justify-center text-on-surface-variant/40">
                                <span class="material-symbols-outlined">image</span>
                            </div>
                        @endif
                    </div>
                    <input type="file" name="report_footer_image" accept="image/*"
                        class="w-full text-sm text-on-surface-variant file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer"
                        onchange="previewImage(this, 'report-footer-preview')">
                    <div class="mt-2 flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-on-surface">
                            <input type="checkbox" name="report_show_footer" value="1" {{ old('report_show_footer', $report['show_footer'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant">
                            Tampilkan footer
                        </label>
                        @if($footerUrl)
                            <label class="inline-flex items-center gap-1.5 text-xs text-on-surface-variant cursor-pointer">
                                <input type="checkbox" name="remove_report_footer" value="1" class="rounded border-outline-variant text-error focus:ring-error">
                                Hapus gambar
                            </label>
                        @endif
                    </div>
                    @error('report_footer_image')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <p class="text-xs text-on-surface-variant">Fallback teks (dipakai bila gambar header belum di-upload):</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Nama Instansi (fallback)</label>
                    <input type="text" name="report_instansi" value="{{ old('report_instansi', $report['instansi'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Alamat Kop (fallback)</label>
                    <input type="text" name="report_kop_alamat" value="{{ old('report_kop_alamat', $report['kop_alamat'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Kota (TTD)</label>
                    <input type="text" name="report_kota" value="{{ old('report_kota', $report['kota'] ?? '') }}" placeholder="Jakarta"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Jabatan Penandatangan</label>
                    <input type="text" name="report_jabatan" value="{{ old('report_jabatan', $report['jabatan'] ?? 'Mengetahui,') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-1">Nama Penandatangan</label>
                    <input type="text" name="report_nama" value="{{ old('report_nama', $report['nama_tertanda'] ?? '') }}"
                        class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-on-surface mt-6">
                        <input type="checkbox" name="report_show_print_meta" value="1" {{ old('report_show_print_meta', $report['show_print_meta'] ?? true) ? 'checked' : '' }} class="rounded border-outline-variant">
                        Tampilkan info cetak
                    </label>
                    <div class="mt-3">
                        <label class="block text-sm font-semibold text-on-surface mb-1">NIP Penandatangan</label>
                        <input type="text" name="report_nip" value="{{ old('report_nip', $report['nip'] ?? '') }}"
                            class="w-full border border-outline-variant rounded-lg px-3 py-2 bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary text-sm">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-outline-variant">
                <button type="submit" class="bg-primary text-on-primary px-6 py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity">
                    Save Settings
                </button>
                <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-on-surface-variant hover:text-on-surface">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="max-w-full max-h-full object-contain">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    @endpush
</x-layouts::app>