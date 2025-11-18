<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\User;
use App\Models\Upload;
use App\Models\Category;

class StatsController
{
    public function user(): void
    {
        $user = AuthMiddleware::handle();
        
        $stats = User::getStatistics($user->id);

        header('Content-Type: application/json');
        echo json_encode($stats);
    }

    public function global(): void
    {
        $user = AuthMiddleware::handle();
        AuthMiddleware::requireRole($user, 'super_admin');

        $stats = Upload::getGlobalStatistics();
        $topCategories = Category::getTopCategories(10);

        header('Content-Type: application/json');
        echo json_encode([
            'overall' => $stats,
            'top_categories' => $topCategories
        ]);
    }
}
