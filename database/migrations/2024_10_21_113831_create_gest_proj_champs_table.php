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
        Schema::create('gest_proj_champs', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->text('valeur')->nullable(); // Peut être un texte ou un chemin de fichier
            $table->string('type')->default('text'); // Ajout d'un type de champ : 'text' ou 'file'
            $table->unsignedBigInteger('champable_id')->nullable();
            $table->string('champable_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gest_proj_champs');
    }
};
