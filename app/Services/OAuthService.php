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
        
        // Encrypt tokens before storing
        $accessTokenEncrypted = $this->encryptToken($accessToken);
        $refreshTokenEncrypted = $refreshToken ? $this->encryptToken($refreshToken) : null;
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        // Delete old tokens for this user
        $stmt = $pdo->prepare('DELETE FROM oauth_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);

        // Insert new token
        $stmt = $pdo->prepare(
            'INSERT INTO oauth_tokens (user_id, access_token_hash, refresh_token_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $accessTokenEncrypted, $refreshTokenEncrypted, $expiresAt]);
    }

    public function getToken(int $userId): ?string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT access_token_hash FROM oauth_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        return $this->decryptToken($result['access_token_hash']);
    }

    private function encryptToken(string $token): string
    {
        $config = require __DIR__ . '/../../config/app.php';
        $key = $config['key'];
        
        if (empty($key)) {
            throw new \RuntimeException('APP_KEY not set. Generate one with: openssl rand -base64 32');
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($token, 'aes-256-cbc', $key, 0, $iv);
        
        // Combine IV and encrypted data, then base64 encode
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decryptToken(string $encryptedToken): string
    {
        $config = require __DIR__ . '/../../config/app.php';
        $key = $config['key'];
        
        if (empty($key)) {
            throw new \RuntimeException('APP_KEY not set');
        }

        $data = base64_decode($encryptedToken);
        list($iv, $encrypted) = explode('::', $data, 2);
        
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
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
