<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EsSnapshot extends Command
{
    protected $signature = 'es:snapshot 
                            {--repo=my_backup : repository name}
                            {--path=C:/mnt/backup : repository path on ES node}
                            {--indices=* : (optional) comma-separated indices to snapshot}';

    protected $description = 'Register an FS snapshot repository, verify it and take a snapshot in Elasticsearch';

    public function handle()
    {
        $esUrl = config('services.elasticsearch.url', env('ELASTIC_URL', 'http://elasticsearch:9200'));
        $repo = $this->option('repo') ?? 'my_backup';
        $repoPath = $this->option('path') ?? '/usr/share/elasticsearch/snapshots';
        $indices = $this->option('indices');
        $indicesParam = count($indices) ? implode(',', $indices) : null;

        $this->info("Using Elasticsearch URL: {$esUrl}");
        $this->info("Repository name: {$repo}");
        $this->info("Repository path (on ES node): {$repoPath}");

        // 1) register repository
        $this->info("Registering repository...");
        $registerResp = Http::timeout(10)->put("{$esUrl}/_snapshot/{$repo}", [
            'type' => 'fs',
            'settings' => [
                'location' => $repoPath,
                'compress' => true,
            ],
        ]);

        if (! $registerResp->successful()) {
            $this->error("Failed registering repository: HTTP {$registerResp->status()}");
            $this->line($registerResp->body());
            return 1;
        }

        $this->info("Repository registered: " . $registerResp->body());

        // 2) verify repository
        $this->info("Verifying repository...");
        $verifyResp = Http::timeout(10)->post("{$esUrl}/_snapshot/{$repo}/_verify");

        if (! $verifyResp->successful()) {
            $this->error("Repository verification failed: HTTP {$verifyResp->status()}");
            $this->line($verifyResp->body());
            return 1;
        }

        $this->info("Repository verification result: " . $verifyResp->body());

        // 3) take snapshot
        $snapshotName = 'snapshot_' . now()->format('Y-m-d_H-i-s');
        $this->info("Creating snapshot: {$snapshotName}");

        $payload = [
            'ignore_unavailable' => true,
            'include_global_state' => true,
        ];

        if ($indicesParam) {
            $payload['indices'] = $indicesParam;
        }

        $createResp = Http::timeout(60)->put("{$esUrl}/_snapshot/{$repo}/{$snapshotName}?wait_for_completion=true", $payload);

        if (! $createResp->successful()) {
            $this->error("Failed to create snapshot: HTTP {$createResp->status()}");
            $this->line($createResp->body());
            return 1;
        }

        $this->info("Snapshot created: " . $createResp->body());

        $this->info("Done.");
        return 0;
    }
}
