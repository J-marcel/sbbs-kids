<?php

use App\Models\Admin;
use App\Models\Module;
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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->time('duration');
            $table->text('competences');
            $table->decimal('price', 10, 2);
            $table->string('libelle');
            $table->string('image');
            $table->foreignIdFor(Module::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Admin::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
