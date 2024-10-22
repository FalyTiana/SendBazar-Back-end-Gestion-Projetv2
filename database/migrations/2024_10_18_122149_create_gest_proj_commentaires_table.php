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
        Schema::create('gest_proj_commentaires', function (Blueprint $table) {
            $table->id();
            $table->text('contenu')->nullable();
            $table->foreignId('gest_com_utilisateur_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('gest_proj_projet_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('file')->nullable(); 
            $table->string('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gest_proj_commentaires');
    }
};
