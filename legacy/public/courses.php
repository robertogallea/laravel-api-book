<?php

require __DIR__.'/../db.php';

// Data access, business logic (none here, it's just a list) and HTML
// rendering all live in the same file: there is no controller, no view
// template, no resource layer separating them.
$courses = $pdo->query('SELECT * FROM courses ORDER BY starts_at')->fetchAll();

?>
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title>Corsi disponibili</title></head>
<body>
<h1>Corsi disponibili</h1>
<ul>
<?php foreach ($courses as $course): ?>
    <?php $remaining = $course['total_seats'] - $course['occupied_seats']; ?>
    <li>
        <a href="course.php?id=<?= (int) $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></a>
        - inizio <?= htmlspecialchars($course['starts_at']) ?>
        - posti liberi: <?= (int) $remaining ?>/<?= (int) $course['total_seats'] ?>
    </li>
<?php endforeach; ?>
</ul>
</body>
</html>
