<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AssignRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Setting up default roles...');
        
        // Konfigurasi: email untuk admin
        $adminEmail = 'daffa@example.com'; // Ganti sesuai kebutuhan
        
        // Step 1: Validasi
        if (User::count() === 0) {
            $this->command->error("❌ No users found!");
            return;
        }
        
        $pegawaiRole = Role::where('name', 'Pegawai')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        
        if (!$pegawaiRole || !$adminRole) {
            $this->command->error("❌ Required roles not found!");
            return;
        }
        
        // Step 2: Clear existing assignments
        \DB::table('model_has_roles')->delete();
        $this->command->info("🧹 Cleared existing assignments");
        
        // Step 3: Assign Pegawai to all users
        User::chunk(100, function ($users) use ($pegawaiRole) {
            foreach ($users as $user) {
                $user->assignRole($pegawaiRole);
            }
        });
        
        $userCount = User::count();
        $this->command->info("✅ Assigned 'Pegawai' role to {$userCount} users");
        
        // Step 4: Assign Admin to specific user
        $adminUser = User::where('email', $adminEmail)->first();
        
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info("👑 Assigned 'Admin' role to: {$adminEmail}");
        } else {
            // Jika email admin tidak ada, assign ke user pertama
            $firstUser = User::first();
            $firstUser->assignRole($adminRole);
            $this->command->info("👑 Assigned 'Admin' role to first user: {$firstUser->email}");
        }
        
        // Step 5: Summary
        $adminCount = User::role('Admin')->count();
        $pegawaiCount = User::role('Pegawai')->count();
        
        $this->command->info("\n📊 Setup completed:");
        $this->command->info("Admin users: {$adminCount}");
        $this->command->info("Pegawai users: {$pegawaiCount}");
    }
}