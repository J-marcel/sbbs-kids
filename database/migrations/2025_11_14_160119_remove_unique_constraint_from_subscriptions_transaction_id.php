<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Supprimer la contrainte unique sur transaction_id
            $table->dropUnique(['transaction_id']);

            // Ajouter un index normal (pour la performance) au lieu de unique
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->unique('transaction_id');
        });
    }
};
