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
namespace App\Http\Middleware;

use App\Models\User;

class AuthMiddleware
{
    public static function handle(): ?User
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $user = User::findById($_SESSION['user_id']);

        if ($user === null) {
            session_destroy();
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        return $user;
    }

    public static function requireRole(User $user, string ...$roles): void
    {
        if (!$user->hasRole(...$roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Insufficient permissions']);
            exit;
        }
    }
}
