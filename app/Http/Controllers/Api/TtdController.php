<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoKey;
use App\Models\Signature;
use App\Models\SignatureString;
use App\Models\ValidationLog;
use App\Services\CryptoService;
use App\Services\FileSigningService;
use App\Services\FileValidator;
use App\Services\QrCodeService;
use App\Services\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Layanan tanda tangan digital (port dari digitalsignature).
 * Auth: Sanctum. Semua endpoint di bawah prefix /api/ttd.
 */
class TtdController extends Controller
{
    public function __construct(
        private FileSigningService $fileSigning,
        private FileValidator $fileValidator,
        private QrCodeService $qr,
        private ValidationService $validation,
        private CryptoService $crypto,
    ) {}

    public function signImage(Request $request): Response
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg|max:10240',
            'expired_date' => 'nullable|date|after:now',
            'label' => 'nullable|string|max:255',
        ]);

        try {
            $this->fileValidator->validateImage($validated['file']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        $result = $this->fileSigning->signFile($validated['file'], $request->user(), $validated['label'] ?? null, $validated['expired_date'] ?? null);
        $signature = $this->record($request, 'image', $result, $validated['file']->getClientOriginalName());

        return response($result['bytes'], 200, [
            'Content-Type' => $validated['file']->getMimeType(),
            'Content-Disposition' => 'attachment; filename="signed-'.$validated['file']->getClientOriginalName().'"',
            'X-Signature-Id' => $signature->id,
        ]);
    }

    public function signPdf(Request $request): Response
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
            'expired_date' => 'nullable|date|after:now',
            'label' => 'nullable|string|max:255',
        ]);

        try {
            $this->fileValidator->validatePdf($validated['file']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        $result = $this->fileSigning->signFile($validated['file'], $request->user(), $validated['label'] ?? null, $validated['expired_date'] ?? null);
        $signature = $this->record($request, 'pdf', $result, $validated['file']->getClientOriginalName());

        return response($result['bytes'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="signed-'.$validated['file']->getClientOriginalName().'"',
            'X-Signature-Id' => $signature->id,
        ]);
    }

    public function signString(Request $request): Response
    {
        $validated = $request->validate([
            'string' => 'required|string|max:500',
            'expired_date' => 'nullable|date|after:now',
            'label' => 'nullable|string|max:255',
        ]);

        $result = $this->fileSigning->signString($request->user(), $validated['string'], $validated['label'] ?? null, $validated['expired_date'] ?? null);
        $signature = $this->record($request, 'string', $result);

        SignatureString::create(['signature_id' => $signature->id, 'original_string' => $validated['string']]);

        $qrPng = $this->qr->generatePng($this->qr->validateUrl($signature->id));

        return response($qrPng, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-'.$signature->id.'.png"',
            'X-Signature-Id' => $signature->id,
            'X-Validate-Url' => $this->qr->validateUrl($signature->id),
        ]);
    }

    public function signatures(Request $request): JsonResponse
    {
        $query = Signature::with('stringData')->where('user_id', $request->user()->id);
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $query->where('label', 'like', '%'.$request->input('search').'%');
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    public function signatureShow(string $id, Request $request): JsonResponse
    {
        $signature = Signature::with('stringData', 'hasUser')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $signature) {
            return response()->json(['message' => 'Tanda tangan tidak ditemukan'], 403);
        }
        $status = $signature->status === 'revoked' ? 'DICABUT' : ($signature->isExpired() ? 'KEDALUWARSA' : 'AKTIF');

        return response()->json([
            'id' => $signature->id, 'type' => $signature->type, 'label' => $signature->label,
            'original_filename' => $signature->original_filename, 'content_hash' => $signature->content_hash,
            'created_at' => $signature->created_at, 'expired_at' => $signature->expired_at,
            'status' => $status, 'string' => $signature->stringData?->original_string,
            'validate_url' => $this->qr->validateUrl($signature->id),
        ]);
    }

    public function revoke(string $id, Request $request): JsonResponse
    {
        $signature = Signature::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $signature) {
            return response()->json(['message' => 'Tanda tangan tidak ditemukan'], 403);
        }
        if ($signature->status !== 'revoked') {
            $signature->status = 'revoked';
            $signature->save();
        }

        return response()->json(['message' => 'Tanda tangan dicabut', 'id' => $signature->id]);
    }

    public function keys(Request $request): JsonResponse
    {
        $keys = CryptoKey::where('user_id', $request->user()->id)->latest()->get(['id', 'fingerprint', 'is_active', 'created_at']);

        return response()->json(['keys' => $keys, 'active_public_key' => $request->user()->activeCryptoKey?->public_key]);
    }

    public function regenerateKeys(Request $request): JsonResponse
    {
        $user = $request->user();
        $newKey = $this->crypto->generateKeyPairForUser($user);
        $oldIds = CryptoKey::where('user_id', $user->id)->where('id', '!=', $newKey->id)->where('is_active', true)->pluck('id');
        if ($oldIds->isNotEmpty()) {
            CryptoKey::whereIn('id', $oldIds)->update(['is_active' => false]);
            Signature::whereIn('crypto_key_id', $oldIds)->where('status', 'active')->update(['status' => 'revoked']);
        }

        return response()->json(['message' => 'Kunci baru dibuat.', 'key_id' => $newKey->id, 'public_key' => $newKey->public_key]);
    }

    public function validateFile(Request $request): JsonResponse
    {
        $validated = $request->validate(['file' => 'required|file']);
        $bytes = file_get_contents($validated['file']->getRealPath());
        $result = $this->validation->validateFile($bytes, $request);
        if (isset($result['signature']['id'])) {
            if ($sig = Signature::find($result['signature']['id'])) {
                $this->validation->log($sig, $result['status'], $request);
            }
        }

        return response()->json($result);
    }

    public function validateById(string $id, Request $request): JsonResponse
    {
        $result = $this->validation->validateById($id, $request);
        if (isset($result['signature']['id'])) {
            if ($sig = Signature::find($result['signature']['id'])) {
                $this->validation->log($sig, $result['status'], $request);
            }
        }

        return response()->json($result);
    }

    public function stats(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        return response()->json([
            'total_signatures' => Signature::where('user_id', $uid)->count(),
            'total_validations' => ValidationLog::whereHas('signature', fn ($q) => $q->where('user_id', $uid))->count(),
            'expiring_soon' => Signature::where('user_id', $uid)->where('status', 'active')->whereNotNull('expired_at')->whereBetween('expired_at', [now(), now()->addDays(7)])->count(),
        ]);
    }

    private function record(Request $request, string $type, array $result, ?string $filename = null): Signature
    {
        $user = $request->user();
        if (! $user->activeCryptoKey) {
            $this->crypto->generateKeyPairForUser($user);
            $user->refresh();
        }

        return Signature::create([
            'user_id' => $user->id,
            'crypto_key_id' => $user->activeCryptoKey->id,
            'type' => $type,
            'label' => $result['metadata']['label'] ?? null,
            'original_filename' => $filename,
            'content_hash' => $result['content_hash'],
            'embedded_signature' => $result['embedded_signature'],
            'expired_at' => $result['expired_at'] ?? null,
        ]);
    }
}
