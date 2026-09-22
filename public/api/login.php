<?php

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$body = jsonBody();
$username = trim((string) ($body['username'] ?? ''));
$password = (string) ($body['password'] ?? '');

if ($username === '' || $password === '') {
    respond(['message' => 'Enter a username and password.'], 422);
}

$authenticator = new AdminAuthenticator(config()['admin']);

if (! $authenticator->matches($username, $password)) {
    // Slow down repeated guesses.
    usleep(300000);

    respond(['message' => 'Wrong username or password.'], 401);
}

(new AdminSession())->signIn();

respond(['username' => $username]);
