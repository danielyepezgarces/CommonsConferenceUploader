<?php

return [
    'client_id' => $_ENV['OAUTH_CLIENT_ID'] ?? '',
    'client_secret' => $_ENV['OAUTH_CLIENT_SECRET'] ?? '',
    'redirect_uri' => $_ENV['OAUTH_REDIRECT_URI'] ?? 'http://localhost:8000/auth/callback',
    'authorization_endpoint' => 'https://meta.wikimedia.org/w/rest.php/oauth2/authorize',
    'token_endpoint' => 'https://meta.wikimedia.org/w/rest.php/oauth2/access_token',
    'userinfo_endpoint' => 'https://meta.wikimedia.org/w/rest.php/oauth2/resource/profile',
    'scopes' => 'basic profile',
];
