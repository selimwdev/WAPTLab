<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\ElasticService;

class IndexEavToEs extends Command {
    protected $signature = 'es:index-eav';
    protected $description = 'Index EAV entities to Elasticsearch';
    public function handle(ElasticService $es) {
    $this->info("Indexing HR...");
    $hr = DB::connection('mysql_hr')->table('entities')->get();
    foreach($hr as $e) {
        $vals = DB::connection('mysql_hr')->table('values as v')
            ->join('attributes as a','v.attribute_id','a.id')
            ->where('v.entity_id',$e->id)
            ->select('a.name','v.value')->get();
        $doc = ['id'=>$e->id];
        foreach($vals as $vv) $doc[$vv->name] = $vv->value;
        $es->indexDocument('hr_data','employee',$e->id,(array)$doc);
    }

    $this->info("Indexing Support...");
    $sp = DB::connection('mysql_support')->table('entities')->get();
    foreach($sp as $e) {
        $vals = DB::connection('mysql_support')->table('values as v')
            ->join('attributes as a','v.attribute_id','a.id')
            ->where('v.entity_id',$e->id)
            ->select('a.name','v.value')->get();
        $doc = ['id'=>$e->id];
        foreach($vals as $vv) $doc[$vv->name] = $vv->value;
        $es->indexDocument('support_data','client',$e->id,(array)$doc);
    }

    $this->info("Indexing Admin...");
    $admin = DB::connection('mysql_admin')->table('entities')->get();
    foreach($admin as $e) {
        $vals = DB::connection('mysql_admin')->table('values as v')
            ->join('attributes as a','v.attribute_id','a.id')
            ->where('v.entity_id',$e->id)
            ->select('a.name','v.value')->get();
        $doc = ['id'=>$e->id];
        foreach($vals as $vv) $doc[$vv->name] = $vv->value;
        $es->indexDocument('admin_data','admin',$e->id,(array)$doc);
    }

    $this->info("Done indexing.");
}

}
