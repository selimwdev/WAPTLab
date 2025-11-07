<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xff_logs', function (Blueprint $table) {
            $table->id();
            $table->string('x_forwarded_for', 1024)->nullable()->comment('Raw X-Forwarded-For header (comma separated)');
            $table->string('first_xff', 45)->nullable()->comment('First IP in X-Forwarded-For list');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xff_logs');
    }
};
