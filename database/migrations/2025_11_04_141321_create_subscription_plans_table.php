<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
             $table->enum('age_group', ['4-7', '8-12', '13-17'])->comment('Groupe d\'âge');
            $table->integer('duration_months')->comment('Durée en mois (1, 3, 6, 12)');
            $table->decimal('price', 10, 2)->comment('Prix en FCFA');
            $table->string('name')->comment('Nom du plan (ex: Plan Enfant 1 mois)');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index unique pour éviter les doublons
            $table->unique(['age_group', 'duration_months']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
