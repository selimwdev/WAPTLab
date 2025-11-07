<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValuesTable extends Migration {
    public function up() {
        Schema::create('values', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('attribute_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index('entity_id');
            $table->index('attribute_id');

            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('values');
    }
}
