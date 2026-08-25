<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // ສ້າງ Admin
        User::create([
            'uuid' => Str::uuid(),
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // ສ້າງ Organizer
        User::create([
            'uuid' => Str::uuid(),
            'name' => 'Event Organizer',
            'email' => 'organizer@example.com',
            'password' => Hash::make('password1235'),
            'role' => 'organizer',
            'status' => 'active',
        ]);
    }
}
