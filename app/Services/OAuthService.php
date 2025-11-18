<?php

namespace App\Services;

use App\Database;
use App\Models\User;
use GuzzleHttp\Client;
use PDO;

class OAuthService
{
    private array $config;
    private Client $client;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/oauth.php';
        $this->client = new Client(['timeout' => 30]);
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $this->config['scopes'],
            'state' => $state,
        ]);

        return $this->config['authorization_endpoint'] . '?' . $params;
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = $this->client->post($this->config['token_endpoint'], [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->config['redirect_uri'],
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['access_token'])) {
            throw new \RuntimeException('Failed to obtain access token');
        }

        return $data;
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = $this->client->get($this->config['userinfo_endpoint'], [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function storeToken(int $userId, string $accessToken, ?string $refreshToken, int $expiresIn): void
    {
        $pdo = Database::getConnection();
        
        // Hash tokens before storing
        $accessTokenHash = hash('sha256', $accessToken);
        $refreshTokenHash = $refreshToken ? hash('sha256', $refreshToken) : null;
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        // Delete old tokens for this user
        $stmt = $pdo->prepare('DELETE FROM oauth_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);

        // Insert new token
        $stmt = $pdo->prepare(
            'INSERT INTO oauth_tokens (user_id, access_token_hash, refresh_token_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $accessTokenHash, $refreshTokenHash, $expiresAt]);
    }

    public function authenticateUser(array $userInfo, string $accessToken, ?string $refreshToken = null, int $expiresIn = 3600): User
    {
        $wikimediaId = (string) $userInfo['sub'];
        $username = $userInfo['username'];
        $avatar = $userInfo['profile_image_url'] ?? null;

        $user = User::findByWikimediaId($wikimediaId);

        if ($user === null) {
            $user = new User();
            $user->wikimedia_id = $wikimediaId;
            $user->username = $username;
            $user->role = 'user';
            $user->avatar = $avatar;
            $user->save();
        } else {
            $user->username = $username;
            $user->avatar = $avatar;
            $user->updateLastLogin();
        }

        $this->storeToken($user->id, $accessToken, $refreshToken, $expiresIn);

        return $user;
    }

    public function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }
}
