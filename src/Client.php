<?php

declare(strict_types=1);

// Minimal API client. Everything goes through the HTTP contract (data-model §9: the seeder never uses SQL).
// Plain PHP with the curl extension, no dependencies.

final class ApiError extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly ?array $body)
    {
        parent::__construct(trim($status.' '.($body['code'] ?? '').' '.($body['message'] ?? '')));
    }
}

final class Client
{
    private function __construct(private string $baseUrl, private ?string $token = null) {}

    public static function login(string $baseUrl, string $username, string $password): self
    {
        $client = new self($baseUrl);
        $res = $client->call('POST', '/auth/login', ['username' => $username, 'password' => $password]);
        $client->token = $res['access_token'];

        return $client;
    }

    public function get(string $path): array
    {
        return $this->call('GET', $path);
    }

    public function post(string $path, array $body = []): ?array
    {
        return $this->call('POST', $path, $body);
    }

    private function call(string $method, string $path, ?array $body = null): ?array
    {
        $ch = curl_init($this->baseUrl.$path);
        $headers = ['Accept: application/json'];
        if ($this->token !== null) {
            $headers[] = 'Authorization: Bearer '.$this->token;
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false) {
            throw new ApiError(0, ['code' => 'NETWORK_ERROR', 'message' => "Cannot reach {$this->baseUrl}"]);
        }
        $data = $status === 204 ? null : json_decode((string) $raw, true);
        if ($status >= 400) {
            throw new ApiError($status, is_array($data) ? $data : null);
        }

        return $data;
    }

    public static function waitForHealth(string $baseUrl, int $tries = 60): void
    {
        for ($i = 0; $i < $tries; $i++) {
            $ch = curl_init($baseUrl.'/health');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
            curl_exec($ch);
            $ok = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
            curl_close($ch);
            if ($ok) {
                return;
            }
            sleep(2);
        }
        throw new RuntimeException("API not reachable at {$baseUrl}");
    }
}

/** Valid-looking 13-digit ISBN: 978 prefix + 9 digits + EAN-13 check digit. */
function isbn(int $n): string
{
    $body = '978'.str_pad((string) $n, 9, '0', STR_PAD_LEFT);
    $sum = 0;
    foreach (str_split($body) as $i => $d) {
        $sum += (int) $d * ($i % 2 === 0 ? 1 : 3);
    }

    return $body.((10 - $sum % 10) % 10);
}

function env_or(string $name, ?string $default = null): string
{
    $v = getenv($name);
    if ($v === false || $v === '') {
        if ($default === null) {
            fwrite(STDERR, "Set {$name}.\n");
            exit(1);
        }

        return $default;
    }

    return $v;
}
