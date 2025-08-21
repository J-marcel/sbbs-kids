<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si les rôles existent déjà pour éviter les doublons
        if (DB::table('roles')->count() > 0) {
            return;
        }

        $roles = [
            ['name' => 'Admin'],
            ['name' => 'Trainer'],
            ['name' => 'Parent'],
            ['name' => 'Student'],
        ];

        foreach ($roles as &$role) {
            $role['created_at'] = Carbon::now();
            $role['updated_at'] = Carbon::now();
        }

        DB::table('roles')->insert($roles);
    }
}
