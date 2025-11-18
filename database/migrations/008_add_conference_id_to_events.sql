ALTER TABLE events
ADD COLUMN conference_id INT DEFAULT NULL AFTER id,
ADD FOREIGN KEY (conference_id) REFERENCES conferences(id) ON DELETE SET NULL,
ADD INDEX idx_conference_id (conference_id);
