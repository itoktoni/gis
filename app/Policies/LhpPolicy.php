<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class LhpPolicy extends BasePolicy
{
    public function import(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }

    public function exportexcel(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }
}
