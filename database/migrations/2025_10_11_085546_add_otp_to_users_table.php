<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('otp_enabled')->default(true);  
            $table->string('otp_code')->nullable();        
            $table->timestamp('otp_expires_at')->nullable(); 
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_enabled','otp_code','otp_expires_at']);
        });
    }
};
