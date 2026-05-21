<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'description' => 'Akses mahasiswa peserta Kerja Praktek.'],
            ['name' => 'admin', 'label' => 'Admin', 'description' => 'Akses administrasi sistem dan data master.'],
            ['name' => 'koordinator_kp', 'label' => 'Koordinator KP', 'description' => 'Akses koordinasi periode, tempat, pembimbing, penguji, dan nilai KP.'],
            ['name' => 'pembimbing_dalam', 'label' => 'Pembimbing Dalam / Dosen', 'description' => 'Akses dosen pembimbing internal.'],
            ['name' => 'pembimbing_lapangan', 'label' => 'Pembimbing Luar / Lapangan', 'description' => 'Akses pembimbing dari tempat kerja praktek.'],
            ['name' => 'penguji', 'label' => 'Penguji', 'description' => 'Akses penguji sidang kerja praktek.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
