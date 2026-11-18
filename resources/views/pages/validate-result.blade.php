<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hasil Validasi</title></head>
<body style="font-family:sans-serif;max-width:640px;margin:0 auto;padding:24px">
<h1>Hasil Validasi</h1>
<p>Status: <strong>{{ $result['status'] ?? '-' }}</strong></p>
<p style="color:#555">{{ $result['message'] ?? '' }}</p>
@if(!empty($result['signature']))
<dl>
<dt><strong>ID</strong></dt><dd>{{ $result['signature']['id'] }}</dd>
<dt><strong>Ditandatangani</strong></dt><dd>{{ $result['signature']['signed_at'] ?? '-' }}</dd>
<dt><strong>Creator</strong></dt><dd>{{ $result['signature']['creator']['name'] ?? '-' }}</dd>
</dl>
@endif
@if(!empty($checkin))
<img src="{{ Storage::url($checkin->photo_path) }}" style="max-width:100%;border-radius:8px" alt="Foto check-in">
<p>{{ $checkin->address }}</p>
@endif
</body>
</html>
