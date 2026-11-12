<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class KmsDocumentPolicy extends BasePolicy
{
    public function chat(User $user): Response
    {
        return $this->accessProtected($user, __FUNCTION__) ? Response::deny() : Response::allow();
    }
}
