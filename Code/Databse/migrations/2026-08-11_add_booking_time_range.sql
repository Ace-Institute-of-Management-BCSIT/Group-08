-- Run this once on databases created before start and finish times were added.
ALTER TABLE booking_requests
    ADD COLUMN start_time TIME NOT NULL DEFAULT '00:00:00' AFTER requested_date,
    ADD COLUMN finish_time TIME NOT NULL DEFAULT '00:00:00' AFTER start_time;

ALTER TABLE hire_requests
    ADD COLUMN requested_finish_time TIME NOT NULL DEFAULT '00:00:00' AFTER requested_time;
