<?php

namespace App\Http\Controllers;

use App\Services\OAuthService;

class AuthController
{
    private OAuthService $oauthService;

    public function __construct()
    {
        $this->oauthService = new OAuthService();
    }

    public function login(): void
    {
        session_start();
        
        $state = $this->oauthService->generateState();
        $_SESSION['oauth_state'] = $state;

        $authUrl = $this->oauthService->getAuthorizationUrl($state);
        
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback(): void
    {
        session_start();

        // Verify state
        $state = $_GET['state'] ?? null;
        if (!$state || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid state parameter']);
            exit;
        }

        unset($_SESSION['oauth_state']);

        // Get authorization code
        $code = $_GET['code'] ?? null;
        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Authorization code not provided']);
            exit;
        }

        try {
            // Exchange code for token
            $tokenData = $this->oauthService->exchangeCodeForToken($code);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? 3600;

            // Get user info
            $userInfo = $this->oauthService->getUserInfo($accessToken);

            // Authenticate user
            $user = $this->oauthService->authenticateUser($userInfo, $accessToken, $refreshToken, $expiresIn);

            // Store user ID in session
            $_SESSION['user_id'] = $user->id;

            // Redirect to dashboard
            header('Location: /dashboard');
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public function logout(): void
    {
        session_start();
        session_destroy();
        
        header('Location: /');
        exit;
    }

    public function me(): void
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $user = \App\Models\User::findById($_SESSION['user_id']);

        if ($user === null) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'avatar' => $user->avatar,
        ]);
    }
}
