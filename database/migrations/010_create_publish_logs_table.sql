CREATE TABLE IF NOT EXISTS publish_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheduled_upload_id INT NOT NULL,
    action ENUM('scheduled', 'processing', 'completed', 'failed', 'cancelled', 'retrying') NOT NULL,
    message TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scheduled_upload_id) REFERENCES scheduled_uploads(id) ON DELETE CASCADE,
    INDEX idx_scheduled_upload_id (scheduled_upload_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
