<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EavSeeder extends Seeder {
    public function run() {
        $faker = \Faker\Factory::create();

        $attrNames = ['name','email','birth_date','phone','position','company'];
        foreach($attrNames as $n) {
            DB::table('attributes')->updateOrInsert(['name'=>$n], ['data_type'=>'string','created_at'=>now(),'updated_at'=>now()]);
        }

        for ($i=0;$i<30;$i++){
            $entityId = DB::connection('mysql_hr')->table('entities')->insertGetId(['namespace'=>'hr','entity_type'=>'employee','created_at'=>now(),'updated_at'=>now()]);
            $attrs = ['name','email','birth_date','phone','position'];
            foreach($attrs as $a){
                $attr = DB::table('attributes')->where('name',$a)->first();
                DB::connection('mysql_hr')->table('values')->insert(['entity_id'=>$entityId,'attribute_id'=>$attr->id,'value'=>$a==='email' ? $faker->unique()->safeEmail : ($a==='birth_date' ? $faker->date() : $faker->word)]);
            }
        }

        for ($i=0;$i<30;$i++){
            $entityId = DB::connection('mysql_support')->table('entities')->insertGetId(['namespace'=>'support','entity_type'=>'client','created_at'=>now(),'updated_at'=>now()]);
            $attrs = ['name','email','birth_date','phone','company'];
            foreach($attrs as $a){
                $attr = DB::table('attributes')->where('name',$a)->first();
                DB::connection('mysql_support')->table('values')->insert(['entity_id'=>$entityId,'attribute_id'=>$attr->id,'value'=>$a==='email' ? $faker->unique()->safeEmail : ($a==='birth_date' ? $faker->date() : $faker->word)]);
            }
        }
    }
}
