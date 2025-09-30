<?php

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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->comment('Numéro du niveau (1, 2, ...)');
            $table->string('name')->comment('tranche d\'âge');
            $table->enum('age_group', ['4-7', '8-12', '13-17'])->default('4-7')->comment('Groupe d\'âge (4-7, 8-12, 13-17)');
            $table->foreignIdFor(Admin::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
