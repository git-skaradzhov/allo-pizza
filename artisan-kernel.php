<?php

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

if (PHP_VERSION_ID < 80200) {
    fwrite(STDERR, "PHP 8.2+ is required to run Laravel.\n");
    fwrite(STDERR, "Use: ./artisan ... or set PHP=/path/to/php82\n");
    exit(1);
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

$kernel->terminate($input, $status);

exit($status);
