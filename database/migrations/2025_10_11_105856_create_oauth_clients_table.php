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
       Schema::create('oauth_clients', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('client_id')->unique();
    $table->string('client_secret');
    $table->text('redirect_uris')->nullable(); 
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
