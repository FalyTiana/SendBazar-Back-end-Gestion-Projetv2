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
        Schema::create('gest_com_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('poste')->nullable();
            $table->string('role')->nullable();
            $table->string('mot_de_passe')->nullable();
            $table->foreignId('gest_com_entreprise_id')->nullable()->constrained()->onDelete('cascade');;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gest_com_utilisateurs');
    }
};
