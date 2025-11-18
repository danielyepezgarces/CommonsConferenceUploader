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
