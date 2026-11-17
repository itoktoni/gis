<?php

namespace App\Services;

use App\Models\Cuti;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class CutiExportService
{
    public function pdf(Cuti $cuti)
    {
        $cuti->loadMissing(['hasUser', 'hasApprover']);
        $sisa = Cuti::sisa($cuti->cuti_id_user, (int) $cuti->cuti_tanggal_mulai->format('Y'));

        return Pdf::loadView('pages.cuti.print', ['cuti' => $cuti, 'sisa' => $sisa])->setPaper('a4');
    }

    public function docx(Cuti $cuti): string
    {
        $cuti->loadMissing(['hasUser', 'hasApprover']);
        $year = (int) $cuti->cuti_tanggal_mulai->format('Y');
        $sisa = Cuti::sisa($cuti->cuti_id_user, $year);
        $terpakai = Cuti::kuota($year) - $sisa;

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection(['marginTop' => 1440, 'marginBottom' => 1440]);
        $center = ['alignment' => Jc::CENTER];
        $both = ['alignment' => Jc::BOTH];

        $section->addText('PERMOHONAN CUTI TRIWULAN / TAHUNAN / BESAR *', ['bold' => true], $center);
        $section->addText('* Coret yang tidak perlu', ['italic' => true, 'size' => 10], $center);
        $section->addTextBreak();

        $section->addText('Yang bertanda tangan di bawah ini');
        $section->addText('Nama                 : '.($cuti->hasUser?->name ?? '-'));
        $section->addText('Bagian               : '.($cuti->cuti_bagian ?? '-'));
        $section->addText('Mengajukan cuti selama  : '.$cuti->cuti_jumlah_hari.' hari ('.$cuti->cuti_jenis.')');
        $section->addText('Mulai tanggal        : '.formatDate($cuti->cuti_tanggal_mulai).' s/d '.formatDate($cuti->cuti_tanggal_selesai));
        $section->addText('Keperluan cuti       : '.$cuti->cuti_keperluan, null, $both);
        $section->addText('Alamat cuti          : '.($cuti->cuti_alamat ?? '-'), null, $both);
        $section->addTextBreak();

        $section->addText('Jakarta, '.now()->format('d-m-Y'), null, ['alignment' => Jc::RIGHT]);
        $section->addText('Pemohon', null, ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(3);
        $section->addText('( '.$cuti->hasUser?->name.' )', null, ['alignment' => Jc::RIGHT]);
        $section->addTextBreak();

        $section->addText('Catatan Monitoring Cuti', ['bold' => true]);
        $section->addText('Diagendakan No         : ...................');
        $section->addText('No Monitoring          : ...................');
        $section->addText('Sisa / Hak Cuti        : '.$sisa.' / '.Cuti::kuota($year).' (hari)');
        $section->addText('Total Cuti Yang Diambil: '.$terpakai.' (hari)');
        $section->addText('Catatan                : '.($cuti->cuti_catatan ?? '......................................................................'));
        $section->addTextBreak();
        $section->addText('Sisa Cuti Tahun '.$year.'  Sisa : '.$sisa.' hari');
        $section->addTextBreak(2);

        $section->addText('Rekomendasi Atasan Langsung', ['bold' => true]);
        $section->addText('.....................................');
        $section->addTextBreak(2);
        $section->addText('(...............................)');
        $section->addTextBreak();

        $section->addText('Keputusan', ['bold' => true]);
        $keputusan = $cuti->cuti_status === 'approved' ? 'Disetujui' : ($cuti->cuti_status === 'rejected' ? 'Ditolak / ditangguhkan' : 'Menunggu keputusan');
        $section->addText('Diijinkan untuk mengambil cuti : '.$keputusan);
        $section->addText('Ditunda / ditangguhkan         : '.($cuti->cuti_status === 'rejected' ? 'Ya' : '-'));
        $section->addTextBreak();
        $section->addText(($cuti->hasApprover?->name ?? '.................').', '.($cuti->cuti_tanggal_approve ? formatDate($cuti->cuti_tanggal_approve) : '................'));
        $section->addTextBreak(2);
        $section->addText('( '.($cuti->hasApprover?->name ?? '..........................................').' )');

        $path = tempnam(sys_get_temp_dir(), 'cuti').'.docx';
        $phpWord->save($path, 'Word2007');

        return $path;
    }
}
