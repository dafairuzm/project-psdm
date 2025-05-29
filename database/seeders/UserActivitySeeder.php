<?php

namespace Database\Seeders;

use App\Models\UserActivity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserActivity::create([
            'user_id' => 1,
            'activity_id' => 1,
        ]);
        UserActivity::create([
            'user_id' => 1,
            'activity_id' => 2,
        ]);
        UserActivity::create([
            'user_id' => 1,
            'activity_id' => 3,
        ]);
        UserActivity::create([
            'user_id' => 2,
            'activity_id' => 1,
        ]);
        UserActivity::create([
            'user_id' => 2,
            'activity_id' => 2,
        ]);
        UserActivity::create([
            'user_id' => 2,
            'activity_id' => 3,
        ]);
        UserActivity::create([
            'user_id' => 3,
            'activity_id' => 1,
        ]);
        UserActivity::create([
            'user_id' => 3,
            'activity_id' => 2,
        ]);
        UserActivity::create([
            'user_id' => 3,
            'activity_id' => 3,
        ]);
    }
}
