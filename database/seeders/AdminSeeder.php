<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si l'admin existe déjà
        if (User::where('email', 'admin@example.com')->exists()) {
            return;
        }

        // Créer l'utilisateur associé
        $user = User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@example.com', // Corrigé: suppression de l'espace
            'phone_number' => '0700000000',
            'number_whatsapp' => '0700000000',
            'role_id' => 1, // ID du rôle admin
            'password' => Hash::make('password123'),
            'is_otp_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        // Créer l'admin lié à cet utilisateur
        Admin::create([
            'user_id' => $user->id,
            'name' => 'Admin Principal',
            'email' => 'admin@example.com',
            'gender' => 'male',
            'phone_number' => '0700000000',
            'number_whatsapp' => '0700000000',
        ]);
    }
}
