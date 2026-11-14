<?php

namespace Database\Seeders;

use App\Models\SuratTemplate;
use Illuminate\Database\Seeder;

class SuratTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'surat_template_kode' => 'MEMO',
                'surat_template_nama' => 'Memorandum Internal',
                'surat_template_keterangan' => 'Struktur: MEMORANDUM - Nomor/Dari/Kepada/Perihal - isi bernomor - penutup',
                'surat_template_isi' => <<<'HTML'
<p style="text-align: center;"><strong>MEMORANDUM</strong></p>
<table>
<tbody>
<tr><td>Nomor</td><td>:</td><td>{{nomor_atasan}}</td></tr>
<tr><td>Dari</td><td>:</td><td>{{dari_jabatan}}</td></tr>
<tr><td>Kepada</td><td>:</td><td>{{kepada}}</td></tr>
<tr><td>Perihal</td><td>:</td><td><strong>{{perihal}}</strong></td></tr>
</tbody>
</table>
<p>Menindaklanjuti {{dasar}} serta mempertimbangkan {{pertimbangan}}, bersama ini disampaikan arahan pelaksanaan kegiatan sebagai berikut:</p>
<ol>
<li><strong>{{poin_1_judul}}</strong><br />{{poin_1_isi}}</li>
<li><strong>{{poin_2_judul}}</strong><br />{{poin_2_isi}}</li>
<li><strong>Dokumentasi dan Pelaporan</strong><br />Seluruh {{pihak}} agar memastikan pelaksanaan kegiatan terdokumentasi dengan baik dan laporan disampaikan kepada {{laporan_kepada}} sebagai bahan evaluasi.</li>
</ol>
<p>Demikian memorandum ini disampaikan untuk menjadi perhatian dan dilaksanakan dengan penuh tanggung jawab.</p>
HTML,
            ],
            [
                'surat_template_kode' => '9CRO',
                'surat_template_nama' => 'Surat Dinas 9CRO',
                'surat_template_keterangan' => 'Struktur: tempat/tanggal - Nomor/Lampiran/Perihal - Kepada - Dasar - isi - penandatangan - tembusan',
                'surat_template_isi' => <<<'HTML'
<p style="text-align: right;">{{kota}}, {{tanggal_surat}}</p>
<table>
<tbody>
<tr><td>Nomor</td><td>:</td><td>{{nomor_manual}}</td></tr>
<tr><td>Lampiran</td><td>:</td><td>{{lampiran}}</td></tr>
<tr><td>Perihal</td><td>:</td><td><strong>{{perihal}}</strong></td></tr>
</tbody>
</table>
<p>Kepada Yth.<br /><strong>{{kepada}}</strong><br />di Tempat</p>
<p><strong>Dasar:</strong></p>
<ol>
<li>{{dasar_1}}</li>
<li>{{dasar_2}}</li>
</ol>
<p>Dengan hormat,</p>
<p>{{isi_pembuka}}</p>
<p>Sehubungan dengan hal tersebut, kami mohon kepada {{kepada_singkat}} agar {{permohonan}} sebesar <strong>{{nominal}}</strong> ({{terbilang}}).</p>
<p>Demikian surat ini disampaikan, atas perhatian dan kerjasamanya, diucapkan terima kasih.</p>
<p><strong>{{jabatan_penandatangan}}</strong></p>
<p><br /><br /><br /><strong>{{nama_penandatangan}}</strong></p>
<p><strong>Tembusan:</strong></p>
<ol>
<li>{{tembusan_1}}</li>
<li>Arsip</li>
</ol>
HTML,
            ],
            [
                'surat_template_kode' => 'ST',
                'surat_template_nama' => 'Surat Tugas',
                'surat_template_keterangan' => 'Penugasan karyawan ke lokasi/kegiatan tertentu',
                'surat_template_isi' => <<<'HTML'
<p style="text-align: center;"><strong>SURAT TUGAS</strong></p>
<p>Yang bertanda tangan di bawah ini, menugaskan kepada:</p>
<table>
<tbody>
<tr><td>Nama</td><td>:</td><td><strong>{{nama}}</strong></td></tr>
<tr><td>NIP/NIK</td><td>:</td><td>{{nip}}</td></tr>
<tr><td>Jabatan</td><td>:</td><td>{{jabatan}}</td></tr>
</tbody>
</table>
<p>Untuk melaksanakan tugas <strong>{{keperluan}}</strong> di <strong>{{tujuan}}</strong> pada tanggal {{tanggal_mulai}} sampai dengan {{tanggal_selesai}}.</p>
<p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>
HTML,
            ],
            [
                'surat_template_kode' => 'CT',
                'surat_template_nama' => 'Surat Cuti',
                'surat_template_keterangan' => 'Pengajuan cuti karyawan',
                'surat_template_isi' => <<<'HTML'
<p style="text-align: center;"><strong>SURAT CUTI</strong></p>
<p>Yang bertanda tangan di bawah ini:</p>
<table>
<tbody>
<tr><td>Nama</td><td>:</td><td><strong>{{nama}}</strong></td></tr>
<tr><td>NIP/NIK</td><td>:</td><td>{{nip}}</td></tr>
<tr><td>Jabatan</td><td>:</td><td>{{jabatan}}</td></tr>
</tbody>
</table>
<p>Mengajukan cuti selama <strong>{{jumlah_hari}} hari</strong>, terhitung mulai tanggal {{tanggal_mulai}} sampai dengan {{tanggal_selesai}} dengan alasan {{alasan}}.</p>
<p>Demikian pengajuan cuti ini dibuat untuk dapat diproses sebagaimana mestinya.</p>
HTML,
            ],
        ];

        foreach ($rows as $row) {
            SuratTemplate::updateOrCreate(
                ['surat_template_kode' => $row['surat_template_kode']],
                $row
            );
        }
    }
}
