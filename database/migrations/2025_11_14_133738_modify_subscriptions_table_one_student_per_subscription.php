<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Student;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Ajouter une colonne student_id directement dans subscriptions
            $table->foreignIdFor(Student::class)->nullable()->after('parent_model_id')->constrained()->cascadeOnDelete();

            // Optionnel : Garder la référence au paiement groupé
            $table->string('payment_group_id')->nullable()->after('transaction_id')->comment('ID pour regrouper les paiements multiples');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'payment_group_id']);
        });
    }
};
