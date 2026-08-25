<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MeetingPolicy
{// ເບິ່ງລາຍຊື່ໄດ້ບໍ?
    public function viewAny(User $user): bool
    {
        return true; // ທັງ Admin ແລະ Organizer ເຂົ້າເຖິງ List ໄດ້ (ແຕ່ Logic Filter ຈະຈັດການໃນ Controller)
    }
// ເບິ່ງລາຍລະອຽດອັນນີ້ໄດ້ບໍ? - ນີ້ຄືຈຸດສຳຄັນທີ່ກັນ 403
   // app/Policies/MeetingPolicy.php
public function view(User $user, Meeting $meeting): bool
{
    if ($user->role === 'admin') return true;
    return $meeting->created_by_user_id === $user->id || $meeting->organizer_id === $user->id;
}

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'organizer']);
    }

    public function update(User $user, Meeting $meeting): bool
{
    if ($user->role === 'admin') return true;
    return $meeting->created_by_user_id === $user->id || $meeting->organizer_id === $user->id;
}

    public function delete(User $user, Meeting $meeting): bool
{
    if ($user->role === 'admin') return true;
    return $meeting->created_by_user_id === $user->id || $meeting->organizer_id === $user->id;
}
}
