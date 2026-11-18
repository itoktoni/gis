<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Validasi Foto</title></head>
<body style="font-family:sans-serif;max-width:640px;margin:0 auto;padding:24px">
<h1>Validasi Foto</h1>
<p style="color:#555">Unggah foto hasil check-in / dokumen bertanda tangan untuk memeriksa keaslian tanda tangan digitalnya.</p>
@if($errors->any())
<ul style="color:#b3261e">
@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
</ul>
@endif
<form method="POST" action="{{ route('validate.check') }}" enctype="multipart/form-data">
@csrf
<input type="file" name="file" accept="image/png,image/jpeg" required>
<button type="submit" style="margin-top:12px;padding:10px 20px">Periksa Keaslian</button>
</form>
</body>
</html>
