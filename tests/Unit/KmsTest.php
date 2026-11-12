<?php

use App\Services\Kms\KmsRetrievalService;
use App\Services\Kms\TextChunker;

it('memotong teks panjang menjadi beberapa chunk', function () {
    $text = str_repeat('Kalimat uji untuk chunking. ', 200);

    $chunks = TextChunker::chunk($text, 1200, 150);

    expect($chunks)->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(1400);
    }
    // Seluruh teks tercakup (awal teks ada di chunk pertama)
    expect($chunks[0])->toStartWith('Kalimat uji');
});

it('mengembalikan teks pendek sebagai satu chunk', function () {
    expect(TextChunker::chunk('Halo dunia', 1200, 150))->toBe(['Halo dunia']);
    expect(TextChunker::chunk('   ', 1200, 150))->toBe([]);
});

it('mengekstrak istilah pencarian dan membuang stopwords', function () {
    expect(KmsRetrievalService::terms('Berapa hari cuti tahunan?!'))->toBe(['hari', 'cuti', 'tahunan']);
    expect(KmsRetrievalService::terms('apa dan yang itu'))->toBe([]);
});
