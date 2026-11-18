<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Milon\Barcode\DNS2D;

class QrCodeService
{
    public function generatePng(string $text): string
    {
        $png = (new DNS2D)->getBarcodePNG($text, 'QRCODE', 8, 8);

        return base64_decode($png, true) ?: '';
    }

    public function validateUrl(string $signatureId): string
    {
        return rtrim(Config::get('app.url'), '/').'/validate/'.$signatureId;
    }
}
