<?php

namespace App\Http\Controllers;

use App\Actions\CreateAction;
use App\Actions\UpdateAction;
use App\Concerns\ControllerTrait;
use App\Enums\RoleEnum;
use App\Http\Requests\GeneralRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    use ControllerTrait;

    protected function share($data = [])
    {
        $default = [
            'model' => $this->model,
            'role' => RoleEnum::getOptions(),
            'kebunOptions' => \App\Models\Kebun::orderBy('kebun_nama')->pluck('kebun_nama', 'kebun_id')->all(),
        ];

        return array_merge($default, $data);
    }

    public function __construct(User $model)
    {
        $this->model = $model::getModel();
    }

    public function postCreate(GeneralRequest $request)
    {
        $kebunIds = $this->validatedKebunIds($request);

        $avatar = $this->handleAvatar($request, null);
        if ($avatar !== null) {
            $request->merge(['avatar' => $avatar]);
        }

        $response = CreateAction::run($request, $this->model);

        if (! empty($response['status']) && ! empty($response['data'])) {
            $response['data']->hasKebunList()->sync($kebunIds);
        }

        return $this->response($response);
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $kebunIds = $this->validatedKebunIds($request);

        $user = $this->model->findOrFail($id);
        $existing = $user->avatar ?? null;

        $avatar = $this->handleAvatar($request, $existing);
        if ($avatar !== $existing) {
            $request->merge(['avatar' => $avatar]);
        }

        $response = UpdateAction::run($request, $id, $this->model);

        if (! empty($response['status'])) {
            $this->model->findOrFail($id)->hasKebunList()->sync($kebunIds);
        }

        return $this->response($response);
    }

    private function handleAvatar(GeneralRequest $request, ?string $existing): ?string
    {
        if ($request->hasFile('avatar')) {
            try {
                $path = uploadFile($request->file('avatar'), 'users', ['max_size' => 2048]);
                $this->deleteUserFile($existing);

                return $path;
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['avatar' => $e->getMessage()]);
            }
        }

        if ($request->boolean('remove_avatar')) {
            $this->deleteUserFile($existing);

            return null;
        }

        return $existing;
    }

    private function deleteUserFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $file = storage_path('app/public/'.$path);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Sinkronisasi kebun yang boleh diakses user.
     * Kosong = tidak dibatasi (akses semua kebun).
     */
    private function validatedKebunIds(GeneralRequest $request): array
    {
        $validated = $request->validate([
            'kebun_ids' => 'nullable|array',
            'kebun_ids.*' => 'exists:kebun,kebun_id',
        ]);

        return array_map('intval', $validated['kebun_ids'] ?? []);
    }
}
