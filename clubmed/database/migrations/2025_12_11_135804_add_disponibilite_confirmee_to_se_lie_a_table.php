<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('se_lie_a', function (Blueprint $table) {
            // AJOUTER LA COLONNE DANS LA TABLE PIVOT
            $table->boolean('disponibilite_confirmee')->default(false); 
        });
    }

    public function down(): void
    {
        Schema::table('se_lie_a', function (Blueprint $table) {
            $table->dropColumn('disponibilite_confirmee');
        });
    }
};
