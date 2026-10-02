<?php

/**
 * Sends JSON requests and returns the decoded reply.
 */
class HttpJson
{
    /**
     * @param array<string, mixed>|string $body form string or data to encode as JSON
     * @param array<int, string> $headers
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public static function send(string $method, string $url, array|string $body = [], array $headers = []): array
    {
        $isForm = is_string($body);
        $payload = $isForm ? $body : json_encode($body);

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => array_merge(
                [$isForm ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json'],
                $headers
            ),
        ]);

        if ($method !== 'GET') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            return ['status' => 0, 'body' => ['error' => ['message' => $error]]];
        }

        $decoded = json_decode((string) $response, true);

        return ['status' => $status, 'body' => is_array($decoded) ? $decoded : []];
    }
}
