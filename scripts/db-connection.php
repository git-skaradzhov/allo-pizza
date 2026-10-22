<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('database.default');
$config = config("database.connections.{$connection}");

if (! is_array($config) || ($config['driver'] ?? '') !== 'mysql') {
    fwrite(STDERR, "This script supports only the mysql connection (got {$connection}).\n");
    exit(1);
}

echo json_encode([
    'app_env' => (string) app()->environment(),
    'host' => (string) ($config['host'] ?? '127.0.0.1'),
    'port' => (int) ($config['port'] ?? 3306),
    'database' => (string) ($config['database'] ?? ''),
    'username' => (string) ($config['username'] ?? ''),
    'password' => (string) ($config['password'] ?? ''),
    'unix_socket' => (string) ($config['unix_socket'] ?? ''),
], JSON_UNESCAPED_SLASHES);
