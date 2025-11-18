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
use App\Http\Middleware\CsrfMiddleware;
use App\Models\Category;

class CategoryController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        
        $limit = (int) ($_GET['limit'] ?? 100);
        $offset = (int) ($_GET['offset'] ?? 0);

        $categories = Category::getAll($limit, $offset);

        header('Content-Type: application/json');
        echo json_encode($categories);
    }

    public function search(): void
    {
        AuthMiddleware::handle();
        
        $query = $_GET['q'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 20);

        if (empty($query)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            return;
        }

        $categories = Category::search($query, $limit);

        header('Content-Type: application/json');
        echo json_encode($categories);
    }

    public function show(int $id): void
    {
        AuthMiddleware::handle();
        
        $category = Category::findById($id);

        if ($category === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($category);
    }

    public function create(): void
    {
        $user = AuthMiddleware::handle();
        AuthMiddleware::requireRole($user, 'super_admin');
        CsrfMiddleware::handle();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            return;
        }

        // Check if category already exists
        if (Category::findByName($data['name']) !== null) {
            http_response_code(409);
            echo json_encode(['error' => 'Category already exists']);
            return;
        }

        $category = new Category();
        $category->name = $data['name'];
        $category->description = $data['description'] ?? null;

        if ($category->save()) {
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode($category);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create category']);
        }
    }

    public function update(int $id): void
    {
        $user = AuthMiddleware::handle();
        AuthMiddleware::requireRole($user, 'super_admin');
        CsrfMiddleware::handle();

        $category = Category::findById($id);

        if ($category === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['name'])) $category->name = $data['name'];
        if (isset($data['description'])) $category->description = $data['description'];

        if ($category->save()) {
            header('Content-Type: application/json');
            echo json_encode($category);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category']);
        }
    }

    public function delete(int $id): void
    {
        $user = AuthMiddleware::handle();
        AuthMiddleware::requireRole($user, 'super_admin');
        CsrfMiddleware::handle();

        $category = Category::findById($id);

        if ($category === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        if ($category->delete()) {
            http_response_code(204);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
    }

    public function top(): void
    {
        AuthMiddleware::handle();
        
        $limit = (int) ($_GET['limit'] ?? 10);
        $categories = Category::getTopCategories($limit);

        header('Content-Type: application/json');
        echo json_encode($categories);
    }
}
