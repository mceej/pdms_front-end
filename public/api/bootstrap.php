<?php

/**
 * Shared setup for the JSON endpoints in this folder.
 */

require_once __DIR__ . '/../../src/AdminAuthenticator.php';
require_once __DIR__ . '/../../src/AdminSession.php';

header('Content-Type: application/json');

/**
 * Load the application configuration.
 *
 * @return array<string, mixed>
 */
function config(): array
{
    return require __DIR__ . '/../../config.php';
}

/**
 * Send a JSON response and stop.
 *
 * @param array<string, mixed> $data
 */
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/**
 * Reject anything that is not the expected HTTP method.
 */
function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        respond(['message' => 'Method not allowed.'], 405);
    }
}

/**
 * Read the JSON body of the request.
 *
 * @return array<string, mixed>
 */
function jsonBody(): array
{
    $body = json_decode((string) file_get_contents('php://input'), true);

    return is_array($body) ? $body : [];
}
