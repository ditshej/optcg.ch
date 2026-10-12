#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Optcg\Data\DataPaths;
use Optcg\Data\Validator;

$rootDir = dirname(__DIR__);
$validator = new Validator(new DataPaths($rootDir));
$errors = $validator->validate();

if ($errors === []) {
    echo "OK: all data files are valid.\n";
    exit(0);
}

echo count($errors) . " problem(s) found:\n\n";

foreach ($errors as $error) {
    echo "- {$error}\n";
}

exit(1);
