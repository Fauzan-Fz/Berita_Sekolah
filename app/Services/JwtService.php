<?php

namespace App\Services;

use App\Models\JwtRefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Hardened Cryptographic Token Service (Defense-in-Depth):
 * - Opaque Nested JWE Payload: All private claims are encrypted via AES-256
 *   before token signing. Public decoders (like jwt.io) see only ciphertext.
 * - Short-Lived Access Tokens (5-15 min) for minimal interception window.
 * - Single-Use Rotating Refresh Tokens with SHA-256 hashing at rest.
 * - Replay Attack & Token Theft Detection: Reusing a revoked refresh token
 *   triggers immediate revocation of all user sessions.
 * - Constant-Time Signature Comparison (hash_equals) preventing timing attacks.
 */
class JwtService
{
    protected string $secret;

    protected string $issuer;

    protected string $audience;

    protected int $accessTtl;

    protected int $refreshTtl;

    protected bool $encryptPayload;

    public function __construct()
    {
        $this->secret = (string) (config('jwt.secret') ?: config('app.key'));
        $this->issuer = (string) (config('jwt.issuer') ?: config('app.url', 'http://localhost:8000'));
        $this->audience = (string) (config('jwt.audience') ?: 'school-news-api');
        $this->accessTtl = (int) (config('jwt.access_ttl') ?: 15);
        $this->refreshTtl = (int) (config('jwt.refresh_ttl') ?: 10080);
        $this->encryptPayload = (bool) (config('jwt.encrypt_payload', true));
    }

    /**
     * Generate an unpredictable, short-lived Access Token (default: 15 min).
     * Any third-party decoder sees only encrypted ciphertext for the user claims.
     */
    public function generateToken(User $user, array $customClaims = [], ?int $ttlMinutes = null): string
    {
        $now = time();
        $ttl = $ttlMinutes ?? $this->accessTtl;
        $exp = $now + ($ttl * 60);

        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        // Sensitive private claims to encrypt
        $privateClaims = array_merge([
            'sub' => (string) $user->id,
            'role' => $user->role,
            'name' => $user->name,
            'email' => $user->email,
        ], $customClaims);

        // Encrypt claims into opaque ciphertext
        $ciphertext = $this->encryptPayload
            ? Crypt::encryptString(json_encode($privateClaims))
            : null;

        // Outer Wire Payload (Zero sensitive data exposed to public decoders)
        $outerPayload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'jti' => (string) Str::uuid(), // Unique anti-replay nonce
            'enc' => $ciphertext,
        ];

        // If encryption was explicitly disabled, fallback to open claims
        if (! $this->encryptPayload) {
            $outerPayload = array_merge($outerPayload, $privateClaims);
        }

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($outerPayload));

        // HMAC-SHA256 signature
        $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $this->secret, true);
        $encodedSignature = $this->base64UrlEncode($signature);

        return "$encodedHeader.$encodedPayload.$encodedSignature";
    }

    /**
     * Verify and decrypt token. Returns validated and decrypted claims array, or null if invalid.
     */
    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        // 1. Verify Header
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? null) !== 'JWT') {
            return null;
        }

        // 2. Constant-time Signature Verification
        $expectedSignature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $this->secret, true);
        $providedSignature = $this->base64UrlDecode($encodedSignature);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            return null; // Signature mismatch / Tampered token
        }

        // 3. Verify Outer Payload
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (! is_array($payload)) {
            return null;
        }

        $now = time();

        // Expired check
        if (! isset($payload['exp']) || $now >= $payload['exp']) {
            return null;
        }

        // Not-before check
        if (isset($payload['nbf']) && $now < $payload['nbf']) {
            return null;
        }

        // Clock skew check
        if (isset($payload['iat']) && $payload['iat'] > ($now + 60)) {
            return null;
        }

        // Issuer & Audience check
        if (isset($payload['iss']) && $payload['iss'] !== $this->issuer) {
            return null;
        }
        if (isset($payload['aud']) && $payload['aud'] !== $this->audience) {
            return null;
        }

        // 4. Decrypt Opaque Private Claims
        if (! empty($payload['enc'])) {
            try {
                $decryptedJson = Crypt::decryptString($payload['enc']);
                $decryptedClaims = json_decode($decryptedJson, true);
                if (is_array($decryptedClaims)) {
                    // Merge decrypted claims (sub, role, name, email) into returned claims
                    $payload = array_merge($payload, $decryptedClaims);
                } else {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        // Must have subject UUID
        if (empty($payload['sub'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Resolve User model directly from validated token.
     */
    public function getUserFromToken(string $token): ?User
    {
        $claims = $this->verifyToken($token);
        if (! $claims || empty($claims['sub'])) {
            return null;
        }

        $user = User::find($claims['sub']);
        if (! $user || ! $user->is_active) {
            return null;
        }

        return $user;
    }

    /**
     * Issue a cryptographically secure, single-use Rotating Refresh Token.
     */
    public function createRefreshToken(User $user, ?string $ipAddress = null, ?string $userAgent = null): string
    {
        $rawToken = bin2hex(random_bytes(32)).'-'.Str::uuid();
        $tokenHash = hash('sha256', $rawToken);

        JwtRefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes($this->refreshTtl),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        return $rawToken;
    }

    /**
     * Rotate a Refresh Token (Single-Use Semantics).
     * Automatically detects Token Theft / Replay Attacks.
     */
    public function rotateRefreshToken(string $rawToken, ?string $ipAddress = null, ?string $userAgent = null): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        $record = JwtRefreshToken::where('token_hash', $tokenHash)->first();

        if (! $record) {
            return null; // Token does not exist
        }

        // REPLAY ATTACK / THEFT DETECTION:
        // If a revoked refresh token is presented again, someone intercepted it!
        // Immediately revoke ALL sessions for this user as a defense measure.
        if ($record->isRevoked()) {
            Log::warning('Security Alert: Replay attack detected on refresh token for user '.$record->user_id);
            JwtRefreshToken::where('user_id', $record->user_id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return null;
        }

        if ($record->isExpired()) {
            return null;
        }

        $user = $record->user;
        if (! $user || ! $user->is_active) {
            return null;
        }

        // Revoke the current refresh token (single-use)
        $record->update(['revoked_at' => now()]);

        // Generate new Access Token + new Rotating Refresh Token
        $newAccessToken = $this->generateToken($user);
        $newRefreshToken = $this->createRefreshToken($user, $ipAddress, $userAgent);

        return [
            'user' => $user,
            'token_type' => 'Bearer',
            'access_token' => $newAccessToken,
            'expires_in' => $this->accessTtl * 60,
            'refresh_token' => $newRefreshToken,
            'refresh_expires_in' => $this->refreshTtl * 60,
        ];
    }

    /**
     * Revoke a single refresh token upon logout.
     */
    public function revokeRefreshToken(string $rawToken): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $record = JwtRefreshToken::where('token_hash', $tokenHash)->first();

        if ($record && ! $record->isRevoked()) {
            $record->update(['revoked_at' => now()]);

            return true;
        }

        return false;
    }

    /**
     * Revoke all active tokens for a user (e.g. password reset / full logout).
     */
    public function revokeAllUserTokens(User $user): int
    {
        return JwtRefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
