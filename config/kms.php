<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management System
    |--------------------------------------------------------------------------
    |
    | Ekstraksi dokumen → potongan teks (chunks) → pencarian FULLTEXT →
    | jawaban LLM (OpenAI-compatible) dengan sitasi nomor halaman.
    |
    */

    // Ukuran target tiap potongan teks (karakter).
    'chunk_size' => (int) env('KMS_CHUNK_SIZE', 1200),

    // Overlap antar potongan agar konteks tidak terputus (karakter).
    'chunk_overlap' => (int) env('KMS_CHUNK_OVERLAP', 150),

    // Jumlah potongan paling relevan yang dikirim sebagai konteks ke LLM.
    'top_k' => (int) env('KMS_TOP_K', 5),

    // Batas total karakter konteks yang dikirim ke LLM.
    'max_context_chars' => (int) env('KMS_MAX_CONTEXT_CHARS', 12000),

];
