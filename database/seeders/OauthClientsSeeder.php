<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OauthClientsSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('mysql')->table('oauth_clients')->insert([
            'name' => 'Main CRM App',
            'client_id' => 'crm_main_client_123',
            'client_secret' => 'secret_456789',
            'redirect_uris' => 'https://crm.example.com/callback',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
