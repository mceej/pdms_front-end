<?php

/**
 * A small cache that survives between requests.
 *
 * PHP forgets everything when a request ends, so anything worth not asking
 * Google for twice has to be written down. Entries live in the system's
 * temporary directory, never under public/, readable only by the user the
 * server runs as.
 *
 * Only put things here that are cheap to lose and safe to keep: a short-lived
 * token, or the fact that one was accepted. Never a password, and never the
 * service-account key.
 */
class FileCache
{
    private string $directory;

    public function __construct(private string $namespace, ?string $directory = null)
    {
        $this->directory = $directory ?? sys_get_temp_dir();
    }

    /**
     * What was stored under a key, or null if it was never there or has aged out.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        $path = $this->pathFor($key);

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $entry = json_decode((string) @file_get_contents($path), true);

        if (! is_array($entry) || ! isset($entry['expires_at'], $entry['value'])) {
            return null;
        }

        if ((int) $entry['expires_at'] <= time()) {
            @unlink($path);

            return null;
        }

        return is_array($entry['value']) ? $entry['value'] : null;
    }

    /**
     * Keep something until a moment in time.
     *
     * Written to a new file and then moved into place, so a request reading at
     * the same time sees either the old entry or the new one, never half of one.
     *
     * @param array<string, mixed> $value
     */
    public function put(string $key, array $value, int $expiresAt): void
    {
        if ($expiresAt <= time()) {
            return;
        }

        $path = $this->pathFor($key);
        $temporary = $path . '.' . getmypid();

        $written = @file_put_contents($temporary, json_encode([
            'expires_at' => $expiresAt,
            'value' => $value,
        ]));

        if ($written === false) {
            return;
        }

        @chmod($temporary, 0600);

        if (! @rename($temporary, $path)) {
            @unlink($temporary);
        }
    }

    /**
     * The key is hashed rather than used as-is, so a file name never carries
     * anything about what it holds, and never anything a path could abuse.
     */
    private function pathFor(string $key): string
    {
        $name = substr(hash('sha256', $this->namespace . '|' . $key), 0, 40);

        return $this->directory . '/dswd-dats-' . $name . '.json';
    }
}
