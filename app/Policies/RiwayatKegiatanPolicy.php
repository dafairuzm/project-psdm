<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class RiwayatKegiatanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_riwayat::kegiatan');
    }

    public function view(User $user, UserActivity $userActivity): bool
    {
        return $user->can('view_riwayat::kegiatan') && $userActivity->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_riwayat::kegiatan');
    }

    public function update(User $user, UserActivity $userActivity): bool
    {
        return $user->can('update_riwayat::kegiatan') && $userActivity->user_id === $user->id;
    }

    public function delete(User $user, UserActivity $userActivity): bool
    {
        return $user->can('delete_riwayat::kegiatan') && $userActivity->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_riwayat::kegiatan');
    }
}