<?php
/** Синхронизировать HAPP_DIRECT_SITES в .env с config/happ_direct_sites.php (hub). */
$root = dirname(__DIR__);
$envFile = $root.'/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

/** @var list<string> $sites */
$sites = include $root.'/config/happ_direct_sites.php';
$directSites = implode(',', $sites);

$key = 'HAPP_DIRECT_SITES';
$line = $key.'='.$directSites;
$pattern = '/^'.preg_quote($key, '/').'=.*$/m';
if (preg_match($pattern, $env)) {
    $env = preg_replace($pattern, $line, $env);
} else {
    $env = rtrim($env)."\n".$line."\n";
}

file_put_contents($envFile, $env);
echo "ENV_SYNC_HAPP_DIRECT_SITES_OK count=".count($sites)." len=".strlen($directSites)."\n";
echo "has_sber=".(str_contains($directSites, 'sberbank.ru') ? 'yes' : 'no')."\n";
