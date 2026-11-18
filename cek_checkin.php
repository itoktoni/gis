<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$rows = App\Models\Checkin::with('hasUser')->latest()->limit(5)->get();
foreach ($rows as $c) {
    echo "id={$c->id} user=" . ($c->hasUser->email ?? '?') . " status={$c->status} lat={$c->latitude} lng={$c->longitude} acc={$c->accuracy} ip={$c->ip_address}\n";
    echo "  failures: " . ($c->failure_reason ?? '-') . "\n";
    $p = json_decode($c->payload_json, true);
    if (is_array($p)) {
        foreach (['mock','dev_mode','net_lat','net_lng','sat_count','sat_used_in_fix','mean_cn0','gps_time','loc_age_ms','motion_still','offline'] as $k) {
            echo "  $k=" . var_export($p[$k] ?? null, true) . "\n";
        }
    }
    echo "---\n";
}
