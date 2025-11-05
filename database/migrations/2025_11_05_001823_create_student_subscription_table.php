<?php

use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subscription', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Student::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Subscription::class)->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Éviter les doublons
            $table->unique(['student_id', 'subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subscription');
    }
};
