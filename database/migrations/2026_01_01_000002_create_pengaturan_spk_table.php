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
        Schema::create('pengaturan_spk', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 60)->unique();
            $table->string('label', 100);
            $table->unsignedBigInteger('nilai');
            $table->unsignedBigInteger('nilai_default');
            $table->string('satuan', 40);
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_spk');
    }
};

