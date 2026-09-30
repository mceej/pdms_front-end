<?php

require_once __DIR__ . '/HttpJson.php';

/**
 * Turns the downloaded service-account key into a Google access token.
 */
class GoogleServiceAccount
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPES = 'https://www.googleapis.com/auth/identitytoolkit'
        . ' https://www.googleapis.com/auth/firebase.database'
        . ' https://www.googleapis.com/auth/userinfo.email';

    /** @var array<string, mixed> */
    private array $key;

    private ?string $accessToken = null;

    private int $expiresAt = 0;

    public function __construct(string $keyPath)
    {
        if (! is_file($keyPath)) {
            throw new RuntimeException('The Firebase service-account key is missing.');
        }

        $key = json_decode((string) file_get_contents($keyPath), true);

        if (! is_array($key) || ! isset($key['client_email'], $key['private_key'])) {
            throw new RuntimeException('The Firebase service-account key is not valid.');
        }

        $this->key = $key;
    }

    /**
     * A token Google accepts, reused until it is close to expiring.
     */
    public function accessToken(): string
    {
        if ($this->accessToken !== null && $this->expiresAt > time() + 60) {
            return $this->accessToken;
        }

        $response = HttpJson::send('POST', self::TOKEN_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->buildAssertion(),
        ]));

        if ($response['status'] !== 200 || ! isset($response['body']['access_token'])) {
            throw new RuntimeException('Google refused the service-account key.');
        }

        $this->accessToken = (string) $response['body']['access_token'];
        $this->expiresAt = time() + (int) ($response['body']['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    /**
     * Build and sign the JWT that is exchanged for an access token.
     */
    private function buildAssertion(): string
    {
        $issuedAt = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $this->key['client_email'],
            'scope' => self::SCOPES,
            'aud' => self::TOKEN_URL,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ];

        $unsigned = self::base64Url(json_encode($header)) . '.' . self::base64Url(json_encode($claims));
        $signature = '';

        if (! openssl_sign($unsigned, $signature, $this->key['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the request to Google.');
        }

        return $unsigned . '.' . self::base64Url($signature);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
