<?php

namespace App\Policies;

class CheckinPolicy extends BasePolicy
{
    public function summary(\App\Models\User $user): \Illuminate\Auth\Access\Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? \Illuminate\Auth\Access\Response::deny() : \Illuminate\Auth\Access\Response::allow();
    }

    public function print(\App\Models\User $user): \Illuminate\Auth\Access\Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? \Illuminate\Auth\Access\Response::deny() : \Illuminate\Auth\Access\Response::allow();
    }

    public function exportexcel(\App\Models\User $user): \Illuminate\Auth\Access\Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? \Illuminate\Auth\Access\Response::deny() : \Illuminate\Auth\Access\Response::allow();
    }
}
