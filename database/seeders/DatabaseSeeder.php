<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Operator
        User::updateOrCreate(
            ['nip' => '12345678'],
            [
                'name' => 'Operator System',
                'email' => 'operator@absensi.com',
                'role' => 'operator',
                'jabatan' => 'Administrator HRD',
                'password' => Hash::make('password'),
            ]
        );

        // Default Karyawan 1
        User::updateOrCreate(
            ['nip' => '1001'],
            [
                'name' => 'Ahmad Pratama',
                'email' => 'ahmad@absensi.com',
                'role' => 'karyawan',
                'jabatan' => 'Software Engineer',
                'password' => Hash::make('password'),
            ]
        );

        // Default Karyawan 2
        User::updateOrCreate(
            ['nip' => '1002'],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti@absensi.com',
                'role' => 'karyawan',
                'jabatan' => 'Staff Keuangan',
                'password' => Hash::make('password'),
            ]
        );

        // Default Karyawan 3
        User::updateOrCreate(
            ['nip' => '1003'],
            [
                'name' => 'Budi Setiawan',
                'email' => 'budi@absensi.com',
                'role' => 'karyawan',
                'jabatan' => 'Operasional',
                'password' => Hash::make('password'),
            ]
        );

        // Seed default schedules for Monday through Friday (Senin - Jumat)
        $days = [
            'senin' => ['jam_masuk' => '08:00:00', 'jam_istirahat' => '12:00:00', 'jam_masuk_istirahat' => '13:00:00', 'jam_pulang' => '17:00:00', 'keterangan' => 'Hari Kerja Senin'],
            'selasa' => ['jam_masuk' => '08:00:00', 'jam_istirahat' => '12:00:00', 'jam_masuk_istirahat' => '13:00:00', 'jam_pulang' => '17:00:00', 'keterangan' => 'Hari Kerja Selasa'],
            'rabu' => ['jam_masuk' => '08:00:00', 'jam_istirahat' => '12:00:00', 'jam_masuk_istirahat' => '13:00:00', 'jam_pulang' => '17:00:00', 'keterangan' => 'Hari Kerja Rabu'],
            'kamis' => ['jam_masuk' => '08:00:00', 'jam_istirahat' => '12:00:00', 'jam_masuk_istirahat' => '13:00:00', 'jam_pulang' => '17:00:00', 'keterangan' => 'Hari Kerja Kamis'],
            'jumat' => ['jam_masuk' => '08:00:00', 'jam_istirahat' => '11:30:00', 'jam_masuk_istirahat' => '13:00:00', 'jam_pulang' => '16:30:00', 'keterangan' => 'Hari Kerja Jumat (Sholat Jumat)'],
        ];

        foreach ($days as $hari => $data) {
            Schedule::updateOrCreate(
                ['hari' => $hari],
                array_merge($data, ['is_libur' => false])
            );
        }

        // Seed default office location settings
        \App\Models\Setting::getOfficeSetting();
    }
}
