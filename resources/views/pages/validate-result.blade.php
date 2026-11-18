<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hasil Validasi</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;margin:0;background:#f4f6fb;color:#1a2333;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:32px 16px}
.card{background:#fff;border:1px solid #e3e8f2;border-radius:18px;padding:28px;max-width:560px;width:100%;box-shadow:0 8px 28px rgba(30,79,168,.08)}
.badge{display:inline-block;padding:8px 18px;border-radius:999px;font-weight:800;font-size:16px;margin:4px 0 10px}
.ok{background:#e6f4ea;color:#1b7a3d}.bad{background:#fdecea;color:#b3261e}.warn{background:#fef3c7;color:#b45309}.unknown{background:#eef1f6;color:#5b6b82}
.msg{color:#5b6b82;font-size:14px;margin:0 0 16px}
dl{display:grid;grid-template-columns:130px 1fr;gap:6px 12px;font-size:14px;margin:0 0 16px}
dt{color:#5b6b82}dd{margin:0;font-weight:600;word-break:break-all}
img{max-width:100%;border-radius:12px;border:1px solid #e3e8f2}
.addr{font-size:13px;color:#5b6b82;margin-top:8px}
.btn{display:inline-block;margin-top:18px;padding:12px 22px;border-radius:12px;background:#2e69d2;color:#fff;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="card">
<h1 style="font-size:22px;margin:0 0 12px">🛡️ Hasil Validasi</h1>
@php
$st = $result['status'] ?? '-';
$cls = $st === 'VALID' ? 'ok' : (in_array($st, ['TIDAK VALID', 'PALSU', 'DICABUT']) ? 'bad' : (in_array($st, ['KEDALUWARSA']) ? 'warn' : 'unknown'));
@endphp
<span class="badge {{ $cls }}">{{ $st }}</span>
<p class="msg">{{ $result['message'] ?? '' }}</p>
@if(!empty($result['signature']))
<dl>
<dt>ID</dt><dd style="font-family:monospace;font-size:12px">{{ $result['signature']['id'] }}</dd>
<dt>Penanda</dt><dd>{{ $signature->label ?? $result['signature']['type'] ?? '-' }}</dd>
<dt>Ditandatangani</dt><dd>{{ $result['signature']['signed_at'] ?? '-' }}</dd>
<dt>Pembuat</dt><dd>{{ $result['signature']['creator']['name'] ?? '-' }}</dd>
</dl>
@endif
@if(!empty($checkin))
<img src="{{ Storage::url($checkin->photo_path) }}" alt="Foto check-in">
<p class="addr">{{ $checkin->address ?? '' }}</p>
@endif
<a class="btn" href="{{ route('validate.form') }}">← Periksa Foto Lain</a>
</div>
</body>
</html>
