<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@wifi-billing.local'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $plans = [
            ['name' => '1 Hour', 'price' => 500, 'duration_minutes' => 60, 'speed_limit_mbps' => 5, 'description' => 'Quick browsing access.'],
            ['name' => '3 Hours', 'price' => 1000, 'duration_minutes' => 180, 'speed_limit_mbps' => 10, 'description' => 'Great for a work session.'],
            ['name' => '24 Hours', 'price' => 2500, 'duration_minutes' => 1440, 'speed_limit_mbps' => 15, 'description' => 'Full day of unlimited access.'],
            ['name' => '7 Days', 'price' => 10000, 'duration_minutes' => 10080, 'speed_limit_mbps' => 20, 'description' => 'Weekly pass, best value.'],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['name' => $plan['name']], $plan + ['currency' => 'TZS', 'is_active' => true]);
        }

        $site = Site::firstOrCreate(
            ['radius_nas_ip' => '10.10.0.1'],
            [
                'name' => 'Demo Branch',
                'location' => 'Dar es Salaam',
                'shared_secret' => 'demo-shared-secret-change-me',
                'device_vendor' => 'mikrotik',
                'is_active' => true,
            ]
        );

        $site->syncRadiusNasEntry();
    }
}
