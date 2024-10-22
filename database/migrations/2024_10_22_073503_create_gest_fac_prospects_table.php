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
        Schema::create('gest_fac_prospects', function (Blueprint $table) {
            $table->id();
            $table->string('nom_societe')->nullable();
            $table->string('nom')->nullable();
            $table->string('email')->nullable();
            $table->string('sexe')->nullable();
            $table->string('telephone')->nullable();;
            $table->string('site_web')->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->nullable();
            $table->string('numero_siren')->nullable();
            $table->string('type')->nullable();
            $table->boolean('client')->default(false);
            $table->foreignId('gest_com_entreprise_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gest_fac_prospects');
    }
};
