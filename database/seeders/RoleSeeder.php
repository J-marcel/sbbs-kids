<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
