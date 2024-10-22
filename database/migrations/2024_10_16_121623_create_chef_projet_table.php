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
        Schema::create('chef_projet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gest_proj_projet_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('gest_com_utilisateur_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_projet');
    }
};
