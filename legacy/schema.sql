-- Legacy "gestionale corsi" schema.
-- No migrations tool, no ORM: this file is applied by hand once, exactly as it
-- would have been years ago when this system was first put together.

CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    starts_at TEXT NOT NULL,
    total_seats INTEGER NOT NULL,
    occupied_seats INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    participant_name TEXT NOT NULL,
    participant_email TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses (id)
);
