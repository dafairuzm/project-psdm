<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"Admin","guard_name":"web","permissions":["view_activity","view_any_activity","create_activity","update_activity","restore_activity","restore_any_activity","replicate_activity","reorder_activity","delete_activity","delete_any_activity","force_delete_activity","force_delete_any_activity","view_activity::category","view_any_activity::category","create_activity::category","update_activity::category","restore_activity::category","restore_any_activity::category","replicate_activity::category","reorder_activity::category","delete_activity::category","delete_any_activity::category","force_delete_activity::category","force_delete_any_activity::category","view_job::title","view_any_job::title","create_job::title","update_job::title","restore_job::title","restore_any_job::title","replicate_job::title","reorder_job::title","delete_job::title","delete_any_job::title","force_delete_job::title","force_delete_any_job::title","view_report","view_any_report","create_report","update_report","restore_report","restore_any_report","replicate_report","reorder_report","delete_report","delete_any_report","force_delete_report","force_delete_any_report","view_role","view_any_role","create_role","update_role","delete_role","delete_any_role","view_user","view_any_user","create_user","update_user","restore_user","restore_any_user","replicate_user","reorder_user","delete_user","delete_any_user","force_delete_user","force_delete_any_user","view_user::activity","view_any_user::activity","create_user::activity","update_user::activity","restore_user::activity","restore_any_user::activity","replicate_user::activity","reorder_user::activity","delete_user::activity","delete_any_user::activity","force_delete_user::activity","force_delete_any_user::activity","widget_ActivityStatsOverview","view_riwayat::kegiatan","view_any_riwayat::kegiatan","create_riwayat::kegiatan","update_riwayat::kegiatan","restore_riwayat::kegiatan","restore_any_riwayat::kegiatan","replicate_riwayat::kegiatan","reorder_riwayat::kegiatan","delete_riwayat::kegiatan","delete_any_riwayat::kegiatan","force_delete_riwayat::kegiatan","force_delete_any_riwayat::kegiatan","view_documentation","view_any_documentation","create_documentation","update_documentation","restore_documentation","restore_any_documentation","replicate_documentation","reorder_documentation","delete_documentation","delete_any_documentation","force_delete_documentation","force_delete_any_documentation","view_note","view_any_note","create_note","update_note","restore_note","restore_any_note","replicate_note","reorder_note","delete_note","delete_any_note","force_delete_note","force_delete_any_note","view_attendance","view_any_attendance","create_attendance","update_attendance","restore_attendance","restore_any_attendance","replicate_attendance","reorder_attendance","delete_attendance","delete_any_attendance","force_delete_attendance","force_delete_any_attendance"]},{"name":"Pegawai","guard_name":"web","permissions":["view_riwayat::kegiatan","view_any_riwayat::kegiatan","create_riwayat::kegiatan","update_riwayat::kegiatan","restore_riwayat::kegiatan","restore_any_riwayat::kegiatan","replicate_riwayat::kegiatan","reorder_riwayat::kegiatan","delete_riwayat::kegiatan","delete_any_riwayat::kegiatan","force_delete_riwayat::kegiatan","force_delete_any_riwayat::kegiatan"]},{"name":"Perencanaan","guard_name":"web","permissions":["view_activity","view_any_activity","view_report","view_any_report","view_user::activity","view_any_user::activity","view_documentation","view_any_documentation","view_note","view_any_note","view_attendance","view_any_attendance"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
