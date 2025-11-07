<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntitiesTable extends Migration {
    public function up() {
        Schema::create('entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('namespace')->nullable(); // 'hr' or 'support' (لو بتستخدم واحدة DB)
            $table->string('entity_type')->nullable();
            $table->timestamps();
            $table->index('namespace');
        });
    }
    public function down() {
        Schema::dropIfExists('entities');
    }
}
