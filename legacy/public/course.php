<?php

require __DIR__.'/../db.php';

$migratedCourses = require __DIR__.'/../migrated_courses.php';

$courseId = (int) ($_GET['id'] ?? 0);

$statement = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$statement->execute([$courseId]);
$course = $statement->fetch();

if (! $course) {
    http_response_code(404);
    echo 'Corso non trovato.';
    exit;
}

$isMigrated = array_key_exists($courseId, $migratedCourses);
$seatsUnavailable = false;

if ($isMigrated) {
    // This course's bookings are no longer this system's own business: its
    // local occupied_seats stopped changing the moment enrollment moved to
    // EventHub, so reading it here would show a number that was only ever
    // true up to the day of the migration.
    $request = curl_init("http://localhost:8000/api/v1/events/{$migratedCourses[$courseId]}");
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $rawResponse = curl_exec($request);
    $httpStatus = curl_getinfo($request, CURLINFO_HTTP_CODE);
    curl_close($request);

    // A failed request or a malformed response must not be allowed to render as a
    // confident-looking "0/0": that is a wrong number, not a missing one, and this
    // page's whole point is to be the trustworthy source for this count.
    $response = $rawResponse === false ? null : json_decode($rawResponse, true);
    $seatsUnavailable = $httpStatus !== 200 || ! isset($response['data']['seats_available'], $response['data']['capacity']);

    if (! $seatsUnavailable) {
        $remaining = $response['data']['seats_available'];
        $totalSeats = $response['data']['capacity'];
    }
} else {
    $remaining = $course['total_seats'] - $course['occupied_seats'];
    $totalSeats = $course['total_seats'];
}

$enrollmentsStatement = $pdo->prepare('SELECT * FROM enrollments WHERE course_id = ? ORDER BY created_at');
$enrollmentsStatement->execute([$courseId]);
$enrollments = $enrollmentsStatement->fetchAll();

?>
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title><?= htmlspecialchars($course['title']) ?></title></head>
<body>
<h1><?= htmlspecialchars($course['title']) ?></h1>
<p>Inizio: <?= htmlspecialchars($course['starts_at']) ?></p>
<?php if ($seatsUnavailable): ?>
    <p>Posti liberi: dati non disponibili (EventHub non raggiungibile).</p>
<?php else: ?>
    <p>Posti liberi: <?= (int) $remaining ?>/<?= (int) $totalSeats ?></p>
<?php endif; ?>

<h2>Iscritti</h2>
<?php if ($isMigrated): ?>
    <p><i>Elenco valido solo fino al passaggio a EventHub: le iscrizioni successive non sono
    più registrate qui.</i></p>
<?php endif; ?>
<ul>
<?php foreach ($enrollments as $enrollment): ?>
    <li><?= htmlspecialchars($enrollment['participant_name']) ?> (<?= htmlspecialchars($enrollment['participant_email']) ?>)</li>
<?php endforeach; ?>
</ul>

<?php if ($isMigrated): ?>
    <p>Le iscrizioni per questo corso sono ora gestite altrove.</p>
<?php elseif ($remaining > 0): ?>
    <h2>Iscriviti</h2>
    <form action="enroll.php" method="post">
        <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
        <label>Nome: <input type="text" name="participant_name" required></label><br>
        <label>Email: <input type="email" name="participant_email" required></label><br>
        <button type="submit">Iscriviti</button>
    </form>
<?php else: ?>
    <p>Corso al completo.</p>
<?php endif; ?>
</body>
</html>
