<?php

namespace App\Services;

use App\Models\Surat;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class SuratExportService
{
    public function pdf(Surat $surat)
    {
        $surat->loadMissing(['hasDivisi', 'hasTemplate']);

        return Pdf::loadView('pages.surat.print', ['surat' => $surat])->setPaper('a4');
    }

    public function docx(Surat $surat): string
    {
        $surat->loadMissing(['hasDivisi', 'hasTemplate']);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection(['marginTop' => 1440, 'marginBottom' => 1440]);

        $header = $section->addText(config('app.name', 'Perusahaan'), ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        unset($header);
        if ($surat->hasDivisi) {
            $section->addText($surat->hasDivisi->divisi_nama, ['size' => 12], ['alignment' => Jc::CENTER]);
        }
        $section->addText(str_repeat('-', 72), ['size' => 8], ['alignment' => Jc::CENTER]);

        $section->addTextBreak();
        $section->addText('Nomor  : '.($surat->surat_nomor ?? '-'));
        if ($surat->surat_perihal) {
            $section->addText('Perihal : '.$surat->surat_perihal);
        }
        if ($surat->surat_kepada) {
            $section->addText('Kepada : '.$surat->surat_kepada);
        }
        if ($surat->surat_dari) {
            $section->addText('Dari     : '.$surat->surat_dari);
        }
        $section->addTextBreak();

        $html = (string) $surat->surat_isi;
        if (trim(strip_tags($html)) === '') {
            $section->addText('(tidak ada isi)', ['italic' => true]);
        } else {
            try {
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
            } catch (\Throwable) {
                foreach (preg_split('/\r\n|\r|\n/', trim(strip_tags($html))) as $para) {
                    $para = trim($para);
                    $section->addText($para === '' ? ' ' : $para, ['size' => 12], ['alignment' => Jc::BOTH, 'spaceAfter' => 120]);
                }
            }
        }

        $section->addTextBreak(2);
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(6000);
        $cell = $table->addCell(3000);
        $cell->addText(formatDate($surat->surat_tanggal), ['size' => 12], ['alignment' => Jc::CENTER]);
        $cell->addTextBreak(3);
        $cell->addText('( ........................................ )', ['size' => 12], ['alignment' => Jc::CENTER]);

        $path = tempnam(sys_get_temp_dir(), 'surat').'.docx';
        $phpWord->save($path, 'Word2007');

        return $path;
    }
}
