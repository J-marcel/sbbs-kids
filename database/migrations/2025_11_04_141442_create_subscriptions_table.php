<?php

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\SubscriptionPlan;
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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
             $table->foreignIdFor(ParentModel::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SubscriptionPlan::class)->constrained()->cascadeOnDelete();

            $table->string('transaction_id')->unique()->comment('ID transaction CinetPay');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method')->default('cinetpay');

            $table->json('cinetpay_data')->nullable()->comment('Données de paiement CinetPay');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
