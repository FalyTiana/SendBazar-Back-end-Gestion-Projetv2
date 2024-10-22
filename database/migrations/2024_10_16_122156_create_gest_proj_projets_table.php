<?php

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
        Schema::create('gest_proj_projets', function (Blueprint $table) {
            $table->id();
            $table->string('titre')->nullable();
            $table->string('date_debut')->nullable();
            $table->string('date_fin')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('gest_com_entreprise_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gest_proj_projets');
    }
};
