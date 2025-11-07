<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleToUsers extends Migration {
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('hr'); // hr أو support
        });
    }
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
}
