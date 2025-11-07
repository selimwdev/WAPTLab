<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Entity;
use App\Models\Attribute;
use App\Models\Value;
use Illuminate\Support\Facades\DB;
use Exception;

class MultiConnectionSeeder extends Seeder
{
    public function run(): void
    {
        $connections = [
            'mysql_hr',
            'mysql_support',
            'mysql_admin', // ✅ أضفنا اتصال جديد
        ];

        foreach ($connections as $conn) {
            DB::connection($conn)->beginTransaction();
            try {
                $entity = Entity::on($conn)->create([
                    'namespace'   => match($conn) {
                        'mysql_hr'      => 'hr.employee',
                        'mysql_support' => 'support.ticket',
                        'mysql_admin'   => 'admin.account',
                        default => 'entity.unknown',
                    },
                    'entity_type' => match($conn) {
                        'mysql_hr'      => 'employee',
                        'mysql_support' => 'ticket',
                        'mysql_admin'   => 'admin',
                        default => 'generic',
                    },
                ]);

                $attrs = [];
                switch ($conn) {
                    case 'mysql_hr':
                        $attrs[] = Attribute::on($conn)->create(['name' => 'full_name', 'data_type' => 'string']);
                        $attrs[] = Attribute::on($conn)->create(['name' => 'position',  'data_type' => 'string']);
                        Value::on($conn)->insert([
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[0]->id, 'value'=>'Jane Doe'],
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[1]->id, 'value'=>'Engineer'],
                        ]);
                        break;

                    case 'mysql_support':
                        $attrs[] = Attribute::on($conn)->create(['name' => 'subject', 'data_type' => 'string']);
                        $attrs[] = Attribute::on($conn)->create(['name' => 'flag_holder', 'data_type' => 'string']);
                        $uuid = (string) Str::uuid();
                        $flag = "NUA{{$uuid}}";
                        Value::on($conn)->insert([
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[0]->id, 'value'=>'Sample support ticket'],
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[1]->id, 'value'=>$flag],
                        ]);
                        info("Inserted support flag into mysql_support: {$flag}");
                        break;

                    case 'mysql_admin':
                        $attrs[] = Attribute::on($conn)->create(['name' => 'admin_name', 'data_type' => 'string']);
                        $attrs[] = Attribute::on($conn)->create(['name' => 'admin_flag', 'data_type' => 'string']);
                        $uuid = (string) Str::uuid();
                        $flag = "NUA{{$uuid}}";
                        Value::on($conn)->insert([
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[0]->id, 'value'=>'Root Admin'],
                            ['entity_id'=>$entity->id, 'attribute_id'=>$attrs[1]->id, 'value'=>$flag],
                        ]);
                        info("Inserted admin flag into mysql_admin: {$flag}");
                        break;
                }

                DB::connection($conn)->commit();
            } catch (Exception $e) {
                DB::connection($conn)->rollBack();
                report($e);
                info("Seeding failed for connection {$conn}: " . $e->getMessage());
            }
        }

        $this->command->info('MultiConnectionSeeder finished.');
    }
}
