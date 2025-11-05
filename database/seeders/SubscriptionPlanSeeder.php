<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Groupe 4-7 ans
            ['age_group' => '4-7', 'duration_months' => 1, 'price' => 100, 'name' => 'Plan Enfant 4-7 ans - 1 mois'],
            ['age_group' => '4-7', 'duration_months' => 3, 'price' => 100, 'name' => 'Plan Enfant 4-7 ans - 3 mois'],
            ['age_group' => '4-7', 'duration_months' => 6, 'price' => 100, 'name' => 'Plan Enfant 4-7 ans - 6 mois'],
            ['age_group' => '4-7', 'duration_months' => 12, 'price' => 100, 'name' => 'Plan Enfant 4-7 ans - 1 an'],

            // Groupe 8-12 ans
            ['age_group' => '8-12', 'duration_months' => 1, 'price' => 100, 'name' => 'Plan Pré-ado 8-12 ans - 1 mois'],
            ['age_group' => '8-12', 'duration_months' => 3, 'price' => 100, 'name' => 'Plan Pré-ado 8-12 ans - 3 mois'],
            ['age_group' => '8-12', 'duration_months' => 6, 'price' => 100, 'name' => 'Plan Pré-ado 8-12 ans - 6 mois'],
            ['age_group' => '8-12', 'duration_months' => 12, 'price' => 100, 'name' => 'Plan Pré-ado 8-12 ans - 1 an'],

            // Groupe 13-17 ans
            ['age_group' => '13-17', 'duration_months' => 1, 'price' => 100, 'name' => 'Plan Ado 13-17 ans - 1 mois'],
            ['age_group' => '13-17', 'duration_months' => 3, 'price' => 100, 'name' => 'Plan Ado 13-17 ans - 3 mois'],
            ['age_group' => '13-17', 'duration_months' => 6, 'price' => 100, 'name' => 'Plan Ado 13-17 ans - 6 mois'],
            ['age_group' => '13-17', 'duration_months' => 12, 'price' => 100, 'name' => 'Plan Ado 13-17 ans - 1 an'],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create(array_merge($plan, [
                'description' => "Accès complet pendant {$plan['duration_months']} mois",
                'is_active' => true
            ]));
        }
    }
}
