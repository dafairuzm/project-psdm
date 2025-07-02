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
        
        // Konfigurasi: email untuk admin dan perencanaan
        $adminEmail = 'daffa@example.com'; // Ganti sesuai kebutuhan
        $perencanaanEmail = 'rani@example.com'; // Ganti sesuai kebutuhan
        
        // Step 1: Validasi
        if (User::count() === 0) {
            $this->command->error("❌ No users found!");
            return;
        }
        
        $pegawaiRole = Role::where('name', 'Pegawai')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $perencanaanRole = Role::where('name', 'Perencanaan')->first();
        
        if (!$pegawaiRole || !$adminRole) {
            $this->command->error("❌ Required roles (Admin/Pegawai) not found!");
            return;
        }
        
        // Buat role Perencanaan jika belum ada
        if (!$perencanaanRole) {
            $perencanaanRole = Role::create(['name' => 'Perencanaan']);
            $this->command->info("✨ Created 'Perencanaan' role");
        }
        
        // Step 2: Clear existing assignments
        \DB::table('model_has_roles')->delete();
        $this->command->info("🧹 Cleared existing assignments");
        
        // Step 3: Assign Pegawai to all users EXCEPT admin and perencanaan
        $excludedEmails = [$adminEmail, $perencanaanEmail];
        
        User::whereNotIn('email', $excludedEmails)->chunk(100, function ($users) use ($pegawaiRole) {
            foreach ($users as $user) {
                $user->assignRole($pegawaiRole);
            }
        });
        
        $pegawaiAssignedCount = User::whereNotIn('email', $excludedEmails)->count();
        $this->command->info("✅ Assigned 'Pegawai' role to {$pegawaiAssignedCount} users");
        
        // Step 4: Assign Admin to specific user
        $adminUser = User::where('email', $adminEmail)->first();
        
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            $this->command->info("👑 Assigned 'Admin' role to: {$adminEmail}");
        } else {
            $this->command->warn("⚠️ Admin user with email '{$adminEmail}' not found!");
        }
        
        // Step 5: Assign Perencanaan to specific user
        $perencanaanUser = User::where('email', $perencanaanEmail)->first();
        
        if ($perencanaanUser) {
            $perencanaanUser->assignRole($perencanaanRole);
            $this->command->info("📋 Assigned 'Perencanaan' role to: {$perencanaanEmail}");
        } else {
            $this->command->warn("⚠️ Perencanaan user with email '{$perencanaanEmail}' not found!");
        }
        
        // Step 6: Summary
        $adminCount = User::role('Admin')->count();
        $perencanaanCount = User::role('Perencanaan')->count();
        $pegawaiCount = User::role('Pegawai')->count();
        
        $this->command->info("\n📊 Setup completed:");
        $this->command->info("Admin users: {$adminCount}");
        $this->command->info("Perencanaan users: {$perencanaanCount}");
        $this->command->info("Pegawai users: {$pegawaiCount}");
        $this->command->info("Total users: " . User::count());
    }
}