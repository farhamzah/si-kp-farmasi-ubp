<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Koordinator KP',
                'email' => 'koordinator@sikp.test',
                'roles' => ['koordinator_kp', 'pembimbing_dalam', 'penguji'],
            ],
            [
                'name' => 'Mahasiswa Demo',
                'email' => 'mahasiswa@sikp.test',
                'roles' => ['mahasiswa'],
            ],
            [
                'name' => 'Dosen Pembimbing Demo',
                'email' => 'dosen@sikp.test',
                'roles' => ['pembimbing_dalam', 'penguji'],
            ],
            [
                'name' => 'Pembimbing Lapangan Demo',
                'email' => 'lapangan@sikp.test',
                'roles' => ['pembimbing_lapangan'],
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'must_change_password' => true,
                    'profile_completed' => false,
                ]
            );

            $roleIds = Role::whereIn('name', $data['roles'])->pluck('id');
            $user->roles()->sync($roleIds);

            if (in_array('mahasiswa', $data['roles'], true)) {
                $user->student()->updateOrCreate([], [
                    'nim' => '221010001',
                    'study_program' => 'Farmasi',
                    'semester' => 6,
                    'class_name' => 'A',
                ]);
            }

            if (in_array('pembimbing_lapangan', $data['roles'], true)) {
                $user->fieldSupervisor()->updateOrCreate([], [
                    'institution_name' => 'Apotek Mitra Farmasi',
                    'position' => 'Apoteker Pembimbing',
                ]);
            }

            if (collect($data['roles'])->intersect(['koordinator_kp', 'pembimbing_dalam', 'penguji'])->isNotEmpty()) {
                $user->lecturer()->updateOrCreate([], [
                    'nidn_nip' => $data['email'] === 'koordinator@sikp.test' ? '0012345601' : '0012345602',
                    'employee_number' => $data['email'] === 'koordinator@sikp.test' ? 'DOS001' : 'DOS002',
                    'study_program' => 'Farmasi',
                    'department' => 'Farmasi Klinis',
                    'expertise' => 'Farmakologi',
                ]);
            }
        }
    }
}
