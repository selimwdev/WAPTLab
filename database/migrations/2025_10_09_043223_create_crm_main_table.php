<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_main', function (Blueprint $table) {
            $table->id();
            $table->string('source_db'); // اسم الداتا بيز الأصلية hr أو support أو غيرها
            $table->integer('source_row_id')->nullable(); // id من الداتا الأصلية
            $table->json('data'); // بيانات الصف كامل كـ JSON
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_main');
    }
};
