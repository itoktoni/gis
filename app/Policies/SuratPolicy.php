<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class SuratPolicy extends BasePolicy
{
    public function generate(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function exportpdf(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function exportdocx(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }
}
