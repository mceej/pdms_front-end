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
        $handle = self::handleFor($method, $url, $body, $headers);
        $response = curl_exec($handle);
        $error = curl_error($handle);
        $reply = self::replyFrom($handle, $response === false ? null : (string) $response, $error);
        curl_close($handle);

        return $reply;
    }

    /**
     * Send several requests at once and return the replies under the same keys.
     *
     * A dashboard needs four branches of the database and none of them waits on
     * another, so asking one after another spends four round trips where one
     * will do. Over a link to Singapore that is most of the page load.
     *
     * @param array<string, array{method?: string, url: string, body?: array<string, mixed>|string,
     *                            headers?: array<int, string>}> $requests
     *
     * @return array<string, array{status: int, body: array<string, mixed>}>
     */
    public static function sendMany(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $multi = curl_multi_init();

        // These all go to the same host, so let them share one connection and
        // one handshake instead of opening a new encrypted link each.
        curl_multi_setopt($multi, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);

        $handles = [];

        foreach ($requests as $key => $request) {
            $handle = self::handleFor(
                $request['method'] ?? 'GET',
                $request['url'],
                $request['body'] ?? [],
                $request['headers'] ?? []
            );

            $handles[$key] = $handle;
            curl_multi_add_handle($multi, $handle);
        }

        do {
            $status = curl_multi_exec($multi, $running);

            if ($running > 0) {
                // Waits for something to happen rather than spinning the CPU.
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $replies = [];

        foreach ($handles as $key => $handle) {
            $content = curl_multi_getcontent($handle);
            $replies[$key] = self::replyFrom($handle, $content, curl_error($handle));
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }

        curl_multi_close($multi);

        return $replies;
    }

    /**
     * @param array<string, mixed>|string $body
     * @param array<int, string> $headers
     */
    private static function handleFor(string $method, string $url, array|string $body, array $headers): CurlHandle
    {
        $isForm = is_string($body);
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            // Needed for several requests to share one connection.
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            // Let the far end send it compressed; a branch of records is mostly
            // repeated field names and shrinks to a fraction of its size.
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => array_merge(
                [$isForm ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json'],
                $headers
            ),
        ]);

        if ($method !== 'GET') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $isForm ? $body : json_encode($body));
        }

        return $handle;
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function replyFrom(CurlHandle $handle, ?string $response, string $error): array
    {
        if ($response === null || $response === false) {
            return ['status' => 0, 'body' => ['error' => ['message' => $error]]];
        }

        $decoded = json_decode($response, true);

        return [
            'status' => (int) curl_getinfo($handle, CURLINFO_HTTP_CODE),
            'body' => is_array($decoded) ? $decoded : [],
        ];
    }
}
