#!/usr/bin/env php
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

/**
 * User Management Script
 * 
 * Usage: 
 *   php manage-users.php promote-user <wikimedia_id> <role>
 *   php manage-users.php list-users
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

use App\Database;
use App\Models\User;

$command = $argv[1] ?? null;

if ($command === 'promote-user') {
    $wikimediaId = $argv[2] ?? null;
    $role = $argv[3] ?? null;

    if (!$wikimediaId || !$role) {
        echo "Usage: php manage-users.php promote-user <wikimedia_id> <role>\n";
        echo "Roles: user, conference_admin, super_admin\n";
        exit(1);
    }

    if (!in_array($role, ['user', 'conference_admin', 'super_admin'])) {
        echo "Invalid role. Valid roles: user, conference_admin, super_admin\n";
        exit(1);
    }

    $user = User::findByWikimediaId($wikimediaId);

    if (!$user) {
        echo "User not found with wikimedia_id: {$wikimediaId}\n";
        exit(1);
    }

    $user->role = $role;
    if ($user->save()) {
        echo "✓ User {$user->username} promoted to {$role}\n";
    } else {
        echo "✗ Failed to update user role\n";
        exit(1);
    }

} elseif ($command === 'list-users') {
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT id, wikimedia_id, username, role, registered_at FROM users ORDER BY id');
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "No users found.\n";
        exit(0);
    }

    echo str_pad('ID', 5) . str_pad('Wikimedia ID', 20) . str_pad('Username', 30) . str_pad('Role', 20) . "Registered\n";
    echo str_repeat('-', 100) . "\n";

    foreach ($users as $user) {
        echo str_pad($user['id'], 5) . 
             str_pad($user['wikimedia_id'], 20) . 
             str_pad($user['username'], 30) . 
             str_pad($user['role'], 20) . 
             $user['registered_at'] . "\n";
    }

} else {
    echo "CommonsEventUploader - User Management\n\n";
    echo "Commands:\n";
    echo "  promote-user <wikimedia_id> <role>  Promote user to a role\n";
    echo "  list-users                           List all users\n";
    echo "\nRoles: user, conference_admin, super_admin\n";
    exit(0);
}
