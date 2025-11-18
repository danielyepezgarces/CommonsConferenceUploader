<?php

return [
    'client_id' => $_ENV['OAUTH_CLIENT_ID'] ?? getLocalSetting('oauth_client_id', ''),
    'client_secret' => $_ENV['OAUTH_CLIENT_SECRET'] ?? getLocalSetting('oauth_client_secret', ''),
    'redirect_uri' => $_ENV['OAUTH_REDIRECT_URI'] ?? getLocalSetting('oauth_redirect_uri', 'http://localhost:8000/auth/callback'),
    'authorization_endpoint' => getLocalSetting('oauth_authorization_endpoint', 'https://meta.wikimedia.org/w/rest.php/oauth2/authorize'),
    'token_endpoint' => getLocalSetting('oauth_token_endpoint', 'https://meta.wikimedia.org/w/rest.php/oauth2/access_token'),
    'userinfo_endpoint' => getLocalSetting('oauth_userinfo_endpoint', 'https://meta.wikimedia.org/w/rest.php/oauth2/resource/profile'),
    'scopes' => getLocalSetting('oauth_scopes', 'basic profile'),
];
