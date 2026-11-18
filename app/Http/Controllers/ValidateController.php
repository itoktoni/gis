<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Services\ValidationService;
use Illuminate\Http\Request;

class ValidateController extends Controller
{
    public function __construct(private ValidationService $validation) {}

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
