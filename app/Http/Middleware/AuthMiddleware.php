<?php

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
