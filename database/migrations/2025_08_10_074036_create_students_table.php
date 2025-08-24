<?php

use App\Models\User;
use App\Models\ParentModel;
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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ParentModel::class);
            $table->string('name');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->enum('age_group', ['4-6', '7-10', '11-15', '16-18'])->default('4-6');
            $table->string('phone_number')->nullable();
            $table->string('number_whatsapp')->nullable();
            $table->string('pin_code')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
