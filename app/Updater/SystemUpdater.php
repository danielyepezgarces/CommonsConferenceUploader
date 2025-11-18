<?php
/**
 * CommonsEventUploader
 * 
 * Copyright (C) 2025 Daniel Yepez Garces
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Updater;

use App\Database;
use PDO;
use PDOException;

/**
 * MediaWiki-style System Updater
 * 
 * Handles database schema updates and migrations similar to MediaWiki's update.php
 */
class SystemUpdater
{
    private PDO $db;
    private string $migrationsPath;
    private array $output = [];
    
    const SCHEMA_VERSION = '1.0.0';
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->migrationsPath = __DIR__ . '/../../database/migrations';
    }
    
    /**
     * Run the updater (main entry point)
     */
    public function run(bool $dryRun = false): array
    {
        $this->log("CommonsEventUploader System Updater", 'info');
        $this->log("====================================\n", 'info');
        
        if ($dryRun) {
            $this->log("Running in DRY RUN mode - no changes will be made\n", 'warn');
        }
        
        // Ensure update tracking table exists
        $this->ensureUpdateTable();
        
        // Get current version
        $currentVersion = $this->getCurrentVersion();
        $this->log("Current schema version: " . ($currentVersion ?: 'not set'), 'info');
        
        // Run pending migrations
        $this->runMigrations($dryRun);
        
        // Update schema version
        if (!$dryRun) {
            $this->updateSchemaVersion(self::SCHEMA_VERSION);
            $this->log("\nSchema updated to version: " . self::SCHEMA_VERSION, 'success');
        }
        
        $this->log("\nUpdate complete!", 'success');
        
        return $this->output;
    }
    
    /**
     * Ensure the system_updates table exists for tracking
     */
    private function ensureUpdateTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS system_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_file VARCHAR(255) UNIQUE NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            schema_version VARCHAR(50),
            INDEX idx_migration (migration_file)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->db->exec($sql);
            $this->log("✓ Update tracking table verified", 'success');
        } catch (PDOException $e) {
            $this->log("✗ Failed to create update tracking table: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Get current schema version
     */
    private function getCurrentVersion(): ?string
    {
        try {
            $stmt = $this->db->query("SELECT schema_version FROM system_updates ORDER BY applied_at DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['schema_version'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Update schema version
     */
    private function updateSchemaVersion(string $version): void
    {
        $stmt = $this->db->prepare("INSERT INTO system_updates (migration_file, schema_version) VALUES (?, ?)");
        $stmt->execute(['schema_version_update', $version]);
    }
    
    /**
     * Run pending migrations
     */
    private function runMigrations(bool $dryRun): void
    {
        $this->log("\nChecking for pending migrations...", 'info');
        
        // Get all migration files
        $migrationFiles = glob($this->migrationsPath . '/*.sql');
        sort($migrationFiles);
        
        if (empty($migrationFiles)) {
            $this->log("No migration files found", 'warn');
            return;
        }
        
        // Get applied migrations
        $appliedMigrations = $this->getAppliedMigrations();
        
        $pendingCount = 0;
        
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            
            if (in_array($filename, $appliedMigrations)) {
                $this->log("⊝ $filename (already applied)", 'info');
                continue;
            }
            
            $pendingCount++;
            
            if ($dryRun) {
                $this->log("→ $filename (would be applied)", 'warn');
                continue;
            }
            
            // Apply migration
            $this->applyMigration($file, $filename);
        }
        
        if ($pendingCount === 0) {
            $this->log("✓ Database is up to date", 'success');
        } else {
            $this->log("\n" . $pendingCount . " migration(s) " . ($dryRun ? "pending" : "applied"), 'info');
        }
    }
    
    /**
     * Get list of applied migrations
     */
    private function getAppliedMigrations(): array
    {
        try {
            $stmt = $this->db->query("SELECT migration_file FROM system_updates WHERE migration_file != 'schema_version_update'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Apply a single migration
     */
    private function applyMigration(string $file, string $filename): void
    {
        $this->log("→ Applying $filename...", 'info');
        
        try {
            $this->db->beginTransaction();
            
            // Read and execute SQL
            $sql = file_get_contents($file);
            $this->db->exec($sql);
            
            // Record migration
            $stmt = $this->db->prepare("INSERT INTO system_updates (migration_file) VALUES (?)");
            $stmt->execute([$filename]);
            
            $this->db->commit();
            
            $this->log("  ✓ Success", 'success');
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->log("  ✗ Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Check if a specific table exists
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if a column exists in a table
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM $tableName LIKE ?");
            $stmt->execute([$columnName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Log a message
     */
    private function log(string $message, string $level = 'info'): void
    {
        $this->output[] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get output log
     */
    public function getOutput(): array
    {
        return $this->output;
    }
}
