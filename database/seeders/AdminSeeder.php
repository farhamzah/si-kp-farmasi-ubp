<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@sikp.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'status' => 'active',
                'must_change_password' => true,
                'profile_completed' => false,
            ]
        );

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
