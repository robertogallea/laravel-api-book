<?php

require __DIR__.'/../db.php';

// Everything a single request needs happens right here: reading the input,
// checking the business rule on seat availability, writing both affected
// rows, and rendering the outcome. Nothing is reused by any other entry
// point, because there is no other entry point: this page is the only way
// the legacy system knows how to enroll someone in a course, for every
// course it still owns.

$migratedCourses = require __DIR__.'/../migrated_courses.php';

$courseId = (int) ($_POST['course_id'] ?? 0);
$participantName = trim((string) ($_POST['participant_name'] ?? ''));
$participantEmail = trim((string) ($_POST['participant_email'] ?? ''));

$statement = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$statement->execute([$courseId]);
$course = $statement->fetch();

if (! $course) {
    http_response_code(404);
    echo 'Corso non trovato.';
    exit;
}

if (array_key_exists($courseId, $migratedCourses)) {
    // Hiding the form in course.php is not enough on its own: this endpoint
    // would still happily accept a direct POST for a course it no longer
    // has any business enrolling anyone into. Closing this path too is what
    // makes the extraction real, not just invisible in the interface.
    http_response_code(409);
    echo 'Le iscrizioni per questo corso sono ora gestite altrove.';
    exit;
}

if ($participantName === '' || $participantEmail === '') {
    http_response_code(422);
    echo 'Nome ed email sono obbligatori.';
    exit;
}

if ($course['occupied_seats'] >= $course['total_seats']) {
    http_response_code(422);
    echo 'Corso al completo.';
    exit;
}

// Two separate statements, no transaction wrapping them: if the second one
// fails, or if two people submit this same form at nearly the same instant,
// the seat count and the enrollment list can drift apart. Nobody has ever
// gone back to fix this, because in years of use it has never (visibly)
// caused a problem worth the effort.
$insert = $pdo->prepare(
    'INSERT INTO enrollments (course_id, participant_name, participant_email, created_at) VALUES (?, ?, ?, ?)'
);
$insert->execute([$courseId, $participantName, $participantEmail, date('Y-m-d H:i:s')]);

$update = $pdo->prepare('UPDATE courses SET occupied_seats = occupied_seats + 1 WHERE id = ?');
$update->execute([$courseId]);

?>
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title>Iscrizione confermata</title></head>
<body>
<h1>Iscrizione confermata</h1>
<p><?= htmlspecialchars($participantName) ?>, sei iscritto al corso <?= htmlspecialchars($course['title']) ?>.</p>
<p><a href="course.php?id=<?= (int) $courseId ?>">Torna al corso</a></p>
</body>
</html>
