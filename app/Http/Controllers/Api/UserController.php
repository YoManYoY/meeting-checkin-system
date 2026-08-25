<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // ດຶງລາຍຊື່ User ທັງໝົດ
    public function index()
    {
        $users = User::latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // ສ້າງ User ໃໝ່
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ສ້າງ User สำເລັດແລ້ວ',
            'data' => $user
        ], 201);
    }

    // ສະແດງຂໍ້ມູນ User ຕາມ ID
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    // ແກ້ໄຂຂໍ້ມູນ User
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $data = $request->only(['name','email','role','status']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'ອັບເດດຂໍ້ມູນ User ສຳເລັດແລ້ວ',
            'data' => $user
        ]);
    }

    // ລຶບ User
    public function destroy(User $user)
    {
        // ป้องกันບໍ່ໃຫ້ Admin ລຶບ Account ຕົນເອງ
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ບໍ່ສາມາດລຶບ Account ຕົນເອງໄດ້'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ລຶບ User ສຳເລັດແລ້ວ'
        ]);
    }
}
