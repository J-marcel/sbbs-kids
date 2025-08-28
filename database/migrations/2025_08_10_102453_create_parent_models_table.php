<?php

use App\Models\User;
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
        Schema::create('parent_models', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('avatar')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('phone_number')->nullable();
            $table->string('number_whatsapp')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_child')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_models');
    }
};
