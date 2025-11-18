#!/usr/bin/env php
<?php

/**
 * Generate Application Key
 * 
 * Usage: php generate-key.php
 */

$key = base64_encode(random_bytes(32));

echo "Generated application key:\n";
echo "APP_KEY={$key}\n\n";
echo "Add this to your .env file.\n";
