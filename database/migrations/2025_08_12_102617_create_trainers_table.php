<?php

use App\Models\User;
use App\Models\Admin;
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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Admin::class)->constrained()->cascadeOnDelete();
            $table->string('avatar')->nullable();
            $table->string('name');
            $table->string('email');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('phone_number')->nullable();
            $table->string('number_whatsapp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
