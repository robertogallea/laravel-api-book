<?php

// Run once with `php seed.php` to populate a handful of courses. There is no
// factory, no seeder class: a plain script with hardcoded inserts is exactly
// how this data has always been loaded.

require __DIR__.'/db.php';

$courses = [
    ['Introduzione a PHP', '2026-09-01 09:00:00', 20],
    ['Laravel per chi viene da PHP puro', '2026-09-15 09:00:00', 15],
    ['Basi di dati relazionali', '2026-10-01 09:00:00', 25],
];

$statement = $pdo->prepare(
    'INSERT INTO courses (title, starts_at, total_seats) VALUES (?, ?, ?)'
);

foreach ($courses as $course) {
    $statement->execute($course);
}

echo count($courses)." courses inserted.\n";
