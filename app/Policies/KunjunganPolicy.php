<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class KunjunganPolicy extends BasePolicy
{
    public function reset(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }
}
