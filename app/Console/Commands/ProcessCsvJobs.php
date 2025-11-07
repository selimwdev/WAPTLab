<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CsvImportController;

class ProcessCsvJobs extends Command
{
    protected $signature = 'csv:process';
    protected $description = 'Process pending CSV import jobs';

    public function handle()
    {
        $jobs = DB::table('csv_jobs')->where('status', 'pending')->get();

        foreach ($jobs as $job) {
            CsvImportController::processCsvJob($job);
        }

        $this->info('✅ كل الملفات اتعالجت');
    }
}
