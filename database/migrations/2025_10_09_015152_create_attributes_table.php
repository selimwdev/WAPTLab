<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributesTable extends Migration {
    public function up() {
        Schema::create('attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('data_type')->default('string');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('attributes');
    }
}
