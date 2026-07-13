<?php

namespace App\Console\Commands;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Adapters\LegacyEnrollmentAdapter;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use PDO;

class MigrateLegacyEnrollmentsCommand extends Command
{
    protected $signature = 'legacy:migrate-enrollments
        {course : The legacy course id}
        {event : The corresponding EventHub event id}
        {--database= : Path to the legacy SQLite database (defaults to ../legacy/database.sqlite)}';

    protected $description = 'Backfill a legacy course\'s existing enrollments into EventHub bookings';

    public function handle(CreateBookingAction $action): int
    {
        $databasePath = $this->option('database') ?? base_path('../legacy/database.sqlite');

        // PDO's SQLite driver silently creates an empty database file for a path that
        // does not exist yet, instead of failing: without this check, a typo'd or stale
        // --database path would "succeed" with 0 enrollments migrated, indistinguishable
        // from a course that genuinely has none.
        if (! file_exists($databasePath)) {
            $this->error("Legacy database not found at {$databasePath}.");

            return self::FAILURE;
        }

        $legacyConnection = new PDO('sqlite:'.$databasePath);
        $legacyConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $event = Event::findOrFail($this->argument('event'));
        $adapter = new LegacyEnrollmentAdapter($legacyConnection);

        $statement = $legacyConnection->prepare('SELECT id FROM enrollments WHERE course_id = ?');
        $statement->execute([$this->argument('course')]);
        $enrollmentIds = $statement->fetchAll(PDO::FETCH_COLUMN);

        $migrated = 0;

        foreach ($enrollmentIds as $enrollmentId) {
            $data = $adapter->translate((int) $enrollmentId);

            try {
                $booking = $action($event, $data);
            } catch (ValidationException $e) {
                $this->error("Enrollment #{$enrollmentId}: ".collect($e->errors())->flatten()->implode(' '));

                continue;
            }

            $this->info("Legacy enrollment #{$enrollmentId} -> booking #{$booking->id}.");
            $migrated++;
        }

        // No tracking of which legacy rows have already been migrated: running this command
        // twice creates duplicate bookings for the same enrollments. Making a backfill like
        // this one safe to repeat is exactly the idempotency problem Chapter 7 covers in
        // general; it is not solved again here.
        $this->info("{$migrated} enrollment(s) migrated for course #{$this->argument('course')}.");

        return self::SUCCESS;
    }
}
