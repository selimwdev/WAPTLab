<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $connections = ['mysql', 'mysql_hr', 'mysql_support'];

        $uuid_mysql = (string) Str::uuid();
        $uuid_shared = (string) Str::uuid();

        $flag_mysql = "NUA{{$uuid_mysql}}";
        $flag_shared = "NUA{{$uuid_shared}}";

        foreach ($connections as $conn) {
            Schema::connection($conn)->create('flags', function (Blueprint $table) {
                $table->id();
                $table->string('flag')->unique();
                $table->string('note')->nullable(); 
                $table->timestamps();
            });
        }

        DB::connection('mysql')->table('flags')->insert([
            'flag' => $flag_mysql,
            'note' => 'primary mysql flag',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['mysql_hr', 'mysql_support'] as $conn) {
            DB::connection($conn)->table('flags')->insert([
                'flag' => $flag_shared,
                'note' => 'shared hr/crm flag',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        info("Flags inserted: mysql={$flag_mysql}, shared(hr+crm)={$flag_shared}");
    }

    public function down(): void
    {
        foreach (['mysql', 'mysql_hr', 'mysql_support'] as $conn) {
            Schema::connection($conn)->dropIfExists('flags');
        }
    }
};
