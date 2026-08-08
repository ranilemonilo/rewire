<?php

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::put(SettingKey::ContactAddress, 'Jakarta, Indonesia');
        Setting::put(SettingKey::ContactEmail, 'hello@recodex.id');
        Setting::put(SettingKey::ContactPhone, '+62 21 0000 0000');

        Setting::put(SettingKey::CompanyName, 'PT. Reka Mitra Teknologi');
        Setting::put(SettingKey::CompanyTagline, 'Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda');
        Setting::put(SettingKey::CompanyVision, 'Menjadi mitra teknologi pilihan utama bagi perusahaan Indonesia yang ingin bertumbuh lewat digitalisasi yang tepat guna.');
        Setting::put(SettingKey::CompanyMission, 'Merancang, membangun, dan merawat solusi teknologi yang andal, aman, dan benar-benar dipakai -- dengan tim yang bekerja sebagai perpanjangan tangan teknis klien.');
        Setting::put(SettingKey::CompanyYearsExperience, '8');
        Setting::put(SettingKey::CompanyTotalClients, '45');
        Setting::put(SettingKey::CompanyTotalProjects, '120');
    }
}