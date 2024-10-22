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
        Schema::table('gest_com_utilisateurs', function (Blueprint $table) {
            //
            $table->string('photo_profil')->nullable()->after('mot_de_passe'); // Ajout du champ après 'mot_de_passe'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gest_com_utilisateurs', function (Blueprint $table) {
            //
            $table->dropColumn('photo_profil'); // Suppression du champ en cas de rollback
        });
    }
};
