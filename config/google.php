<?php
// ─── Google OAuth Configuration ───
// Get your credentials at: https://console.cloud.google.com/apis/credentials
// Authorized redirect URI: http://localhost/arsy%20delievry/auth/google-callback.php

define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  APP_URL . '/auth/google-callback.php');
