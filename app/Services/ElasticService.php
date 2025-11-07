<?php
namespace App\Services;

class ElasticService {
    protected $host;
    public function __construct() {
        $this->host = config('services.es.host', 'http://elasticsearch:9200');
    }

    public function indexDocument($index, $type, $id, array $doc) {
        $url = "{$this->host}/{$index}/{$type}/{$id}";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($doc));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $r = curl_exec($ch); curl_close($ch);
        return json_decode($r, true);
    }

    public function search($index, array $body) {
        $url = "{$this->host}/{$index}/_search";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $r = curl_exec($ch); curl_close($ch);
        return json_decode($r, true);
    }
}
