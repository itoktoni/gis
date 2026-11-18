<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Validasi Foto — APN Monitoring</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;margin:0;background:#f4f6fb;color:#1a2333;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:32px 16px}
.card{background:#fff;border:1px solid #e3e8f2;border-radius:18px;padding:28px;max-width:560px;width:100%;box-shadow:0 8px 28px rgba(30,79,168,.08)}
h1{font-size:22px;margin:0 0 6px}.sub{color:#5b6b82;font-size:14px;margin:0 0 20px}
.drop{border:2px dashed #2e69d2;border-radius:14px;padding:24px 16px;text-align:center;background:#f2f6fe;transition:.15s}
.drop.dragover{background:#e0ebfc;border-color:#1e4fa8}
.drop input{display:none}.drop .big{font-size:40px}
.pick{display:inline-block;margin-top:12px;padding:11px 26px;border-radius:10px;background:#fff;border:2px solid #2e69d2;color:#2e69d2;font-size:14px;font-weight:700;cursor:pointer}
.pick:hover{background:#e8f0fd}
.btn{display:block;width:100%;margin-top:14px;padding:14px;border:0;border-radius:12px;background:#2e69d2;color:#fff;font-size:16px;font-weight:700;cursor:pointer}
.btn:hover{filter:brightness(1.08)}.btn:disabled{background:#9db4dd;cursor:not-allowed}
.err{background:#fdecea;color:#b3261e;border-radius:10px;padding:10px 14px;font-size:14px;margin:0 0 14px}
ol{font-size:14px;color:#5b6b82;padding-left:20px;margin:20px 0 0}ol li{margin-bottom:6px}
.file-name{font-size:13px;color:#1e4fa8;margin-top:12px;word-break:break-all;min-height:18px}
</style>
</head>
<body>
<div class="card">
<h1>🛡️ Validasi Foto</h1>
<p class="sub">Unggah foto hasil check-in / dokumen bertanda tangan untuk memeriksa keaslian tanda tangan digitalnya.</p>
@if($errors->any())
<div class="err">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('validate.check') }}" enctype="multipart/form-data">
@csrf
<div class="drop" id="drop">
<span class="big">📷</span><br>
<span style="font-size:14px;color:#5b6b82">Seret foto ke sini atau</span><br>
<label class="pick" for="file">Pilih Foto</label>
<input type="file" name="file" id="file" accept="image/png,image/jpeg" required>
<div class="file-name" id="fileName">Belum ada file dipilih</div>
</div>
<button class="btn" type="submit" id="submitBtn" disabled>Periksa Keaslian</button>
</form>
<ol>
<li>Foto yang <strong>asli dari aplikasi</strong> → VALID.</li>
<li>Foto yang <strong>diubah 1 piksel saja</strong> → TIDAK VALID.</li>
<li>Foto <strong>tanpa tanda tangan</strong> → TIDAK DITEMUKAN.</li>
</ol>
</div>
<script>
var f=document.getElementById('file'),n=document.getElementById('fileName'),
    btn=document.getElementById('submitBtn'),drop=document.getElementById('drop');
function refresh(){var ok=f.files.length>0;n.textContent=ok?('Dipilih: '+f.files[0].name):'Belum ada file dipilih';btn.disabled=!ok;}
f.addEventListener('change',refresh);
['dragover','dragenter'].forEach(function(e){drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.add('dragover');});});
['dragleave','drop'].forEach(function(e){drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.remove('dragover');});});
drop.addEventListener('drop',function(ev){if(ev.dataTransfer.files.length){f.files=ev.dataTransfer.files;refresh();}});
refresh();
</script>
</body>
</html>
