<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class FileValidator
{
    /**
     * Validate that the uploaded file is a genuine, well-formed PNG or JPG/JPEG
     * image: checks the magic signature, the PNG chunk structure (with CRC) and
     * that the file ends exactly at the image's end marker with no trailing
     * bytes. This rejects renamed files, truncated/corrupt images, and polyglot
     * files that smuggle an appended payload after a valid image.
     */
    public function validateImage(UploadedFile $file): void
    {
        $bytes = file_get_contents($file->getRealPath());

        $size = @getimagesize($file->getRealPath());
        if ($size === false) {
            throw new RuntimeException('Berkas bukan gambar yang valid.');
        }

        $type = $size[2];
        if ($type === IMAGETYPE_PNG) {
            $this->assertValidPng($bytes);
        } elseif ($type === IMAGETYPE_JPEG) {
            $this->assertValidJpeg($bytes);
        } else {
            throw new RuntimeException('Format gambar harus PNG atau JPG/JPEG.');
        }
    }

    private function assertValidPng(string $bytes): void
    {
        $sig = "\x89PNG\r\n\x1a\n";
        if (! str_starts_with($bytes, $sig)) {
            throw new RuntimeException('Berkas bukan PNG yang valid.');
        }

        $pos = strlen($sig);
        $len = strlen($bytes);
        $sawIend = false;

        while ($pos + 12 <= $len) {
            $chunkLen = unpack('N', substr($bytes, $pos, 4))[1];
            $type = substr($bytes, $pos + 4, 4);
            $dataStart = $pos + 8;
            $dataEnd = $dataStart + $chunkLen;

            if ($dataEnd + 4 > $len) {
                throw new RuntimeException('PNG rusak: panjang chunk melebihi isi file.');
            }

            $crcExpected = unpack('N', substr($bytes, $dataEnd, 4))[1];
            $crcActual = crc32(substr($bytes, $pos + 4, 4 + $chunkLen));
            if ($crcActual !== $crcExpected) {
                throw new RuntimeException('PNG rusak: CRC chunk tidak valid.');
            }

            $pos = $dataEnd + 4;

            if ($type === 'IEND') {
                $sawIend = true;
                break;
            }
        }

        if (! $sawIend) {
            throw new RuntimeException('PNG tidak lengkap: penanda akhir (IEND) tidak ditemukan.');
        }

        // No trailing bytes are allowed after the IEND chunk.
        if ($pos !== $len) {
            throw new RuntimeException('PNG mengandung data tambahan setelah penanda akhir.');
        }
    }

    private function assertValidJpeg(string $bytes): void
    {
        if (! str_starts_with($bytes, "\xFF\xD8")) {
            throw new RuntimeException('Berkas bukan JPEG yang valid.');
        }

        // JPEG must end with the End-Of-Image marker FF D9.
        if (! str_ends_with($bytes, "\xFF\xD9")) {
            throw new RuntimeException('JPEG rusak atau mengandung data tambahan di akhir.');
        }
    }

    /**
     * Validate that the uploaded file is a genuine PDF by checking the "%PDF-"
     * signature near the start of the file content.
     */
    public function validatePdf(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Tidak dapat membaca berkas.');
        }

        $header = fread($handle, 1024);
        fclose($handle);

        if (! str_starts_with(ltrim($header), '%PDF-')) {
            throw new RuntimeException('Berkas bukan PDF yang valid.');
        }
    }
}