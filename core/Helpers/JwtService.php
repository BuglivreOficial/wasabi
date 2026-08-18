<?php
namespace Core\Helpers;

use Firebase\JWT\JWT;

class JwtService
{
    private string $secret;
    private string $algo = 'HS256';
    private int $expirationSeconds;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
        $this->expirationSeconds = 60 * 60 * 24 * 7; // 1 hora

        if (empty($this->secret)) {
            throw new \RuntimeException('JWT_SECRET não configurado no ambiente.');
        }
    }

    public function generate(array $payload): string
    {
        $now = time();

        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->expirationSeconds,
        ]);

        return JWT::encode($claims, $this->secret, $this->algo);
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new \Firebase\JWT\Key($this->secret, $this->algo));
    }
}