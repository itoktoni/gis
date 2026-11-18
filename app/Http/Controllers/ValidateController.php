<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class ValidateController extends Controller
{
    public function __construct(private ValidationService $validation) {}

    /** Form validasi publik: upload foto hasil check-in/TTD. GET /validate */
    public function form()
    {
        return view('pages.validate-form');
    }

    /** Verifikasi file upload: asli (VALID) atau tidak. POST /validate */
    public function verify(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg|max:10240',
        ]);

        $bytes = file_get_contents($request->file('file')->getRealPath());
        $result = $this->validation->validateFile($bytes, $request);

        $signature = null;
        $checkin = null;
        if (! empty($result['signature']['id'])) {
            $signature = Signature::with('hasUser')->find($result['signature']['id']);
            if ($signature) {
                $this->validation->log($signature, $result['status'], $request);
                $checkin = \App\Models\Checkin::with('hasUser')->where('signature_id', $signature->id)->first();
                if ($checkin && $checkin->status === 'fake') {
                    $result['status'] = 'PALSU';
                    $result['message'] = 'Data check-in tidak berasal dari aplikasi resmi: '.($checkin->failure_reason ?? '');
                }
            }
        }

        return view('pages.validate-result', ['result' => $result, 'signature' => $signature, 'checkin' => $checkin]);
    }

    /** Halaman validasi publik via QR: GET /validate/{id} */
    public function show(string $id, Request $request)
    {
        $checkin = \App\Models\Checkin::with('hasUser')->where('signature_id', $id)->first();
        $result = $this->validation->validateById($id, $request);
        if ($checkin && $checkin->status === 'fake') {
            $result['status'] = 'PALSU';
            $result['message'] = 'Data check-in tidak berasal dari aplikasi resmi: '.($checkin->failure_reason ?? '');
        }
        $signature = Signature::with('hasUser')->find($id);

        return view('pages.validate-result', ['result' => $result, 'signature' => $signature, 'checkin' => $checkin]);
    }
}
