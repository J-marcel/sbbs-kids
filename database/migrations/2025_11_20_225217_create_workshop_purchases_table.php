<?php

use App\Models\Student;
use App\Models\Workshop;
use App\Models\ParentModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ParentModel::class)->constrained()->cascadeOnDelete()->comment('Parent qui fait l\'achat');
            $table->foreignIdFor(Student::class)->constrained()->cascadeOnDelete()->comment('Élève inscrit au workshop');
            $table->foreignIdFor(Workshop::class)->constrained()->cascadeOnDelete();

            $table->string('transaction_id')->comment('ID de transaction CinetPay');
            $table->string('payment_group_id')->nullable()->comment('ID pour regrouper plusieurs achats en un seul paiement');

            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method')->default('cinetpay');

            $table->json('cinetpay_data')->nullable();
            $table->timestamp('purchased_at')->nullable()->comment('Date d\'achat validé');

            $table->timestamps();
            $table->softDeletes();

            // Un student ne peut acheter le même workshop qu'une seule fois
            $table->unique(['student_id', 'workshop_id']);

            // Index pour recherche rapide
            $table->index('transaction_id');
            $table->index('payment_group_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_purchases');
    }
};
