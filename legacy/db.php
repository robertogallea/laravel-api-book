<?php

// Single, global database connection: every page of this application includes
// this file and reuses the same $pdo variable directly. There is no
// dependency injection and no repository in between, on purpose: this is the
// legacy system as it was actually built, years before EventHub existed.

$databaseFile = __DIR__.'/database.sqlite';
$isFirstRun = ! file_exists($databaseFile);

$pdo = new PDO('sqlite:'.$databaseFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($isFirstRun) {
    $pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
}
