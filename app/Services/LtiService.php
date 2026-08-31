<?php

namespace App\Services;

use App\Models\LtiNonce;
use App\Models\LtiPlatform;
use App\Models\LtiSession;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LtiService
{
    /**
     * Launch diagnostics contain platform user identifiers, so they are only
     * written when LTI_DEBUG is explicitly enabled.
     */
    public function debug(string $message, array $context = []): void
    {
        if (config('lti.debug')) {
            Log::debug($message, $context);
        }
    }

    public function findPlatformByIssuer(string $issuer, string $clientId): ?LtiPlatform
    {
        // Normalize issuer by removing trailing slash
        $normalizedIssuer = rtrim($issuer, '/');

        $this->debug('LTI Platform Lookup', [
            'received_issuer' => $issuer,
            'normalized_issuer' => $normalizedIssuer,
            'received_client_id' => $clientId,
        ]);

        // First try exact match
        $platform = LtiPlatform::where('issuer', $issuer)
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        // If not found, try with normalized issuer (without trailing slash)
        if (! $platform) {
            $platform = LtiPlatform::where(function ($query) use ($issuer, $normalizedIssuer) {
                $query->where('issuer', $issuer)
                    ->orWhere('issuer', $normalizedIssuer)
                    ->orWhere('issuer', $normalizedIssuer.'/');
            })
                ->where('client_id', $clientId)
                ->where('is_active', true)
                ->first();
        }

        $this->debug('LTI Platform Lookup Result', [
            'found' => $platform ? true : false,
            'platform_id' => $platform?->id,
        ]);

        return $platform;
    }

    public function validateIdToken(string $idToken, LtiPlatform $platform): ?array
    {
        try {
            $this->debug('LTI Token Validation Start', ['platform_id' => $platform->id]);

            $jwks = $this->getPlatformJwks($platform);
            $this->debug('LTI JWKS fetched', ['jwks_keys_count' => count($jwks['keys'] ?? [])]);

            $decoded = JWT::decode($idToken, JWK::parseKeySet($jwks));
            $claims = $this->objectToArray($decoded);

            $this->debug('LTI Token decoded successfully', [
                'iss' => $claims['iss'] ?? 'missing',
                'aud' => $claims['aud'] ?? 'missing',
                'message_type' => $claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? 'missing',
            ]);

            // Validate required claims - use flexible issuer matching
            $tokenIssuer = $claims['iss'] ?? null;
            $normalizedTokenIssuer = rtrim($tokenIssuer, '/');
            $normalizedPlatformIssuer = rtrim($platform->issuer, '/');

            if ($normalizedTokenIssuer !== $normalizedPlatformIssuer) {
                Log::error('LTI Issuer mismatch', [
                    'token_issuer' => $tokenIssuer,
                    'platform_issuer' => $platform->issuer,
                ]);

                return null;
            }

            if (($claims['aud'] ?? null) !== $platform->client_id) {
                // aud can be array
                $aud = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
                if (! in_array($platform->client_id, $aud)) {
                    Log::error('LTI Audience mismatch', [
                        'token_aud' => $claims['aud'] ?? 'missing',
                        'platform_client_id' => $platform->client_id,
                    ]);

                    return null;
                }
            }

            // Validate nonce
            $nonce = $claims['nonce'] ?? null;
            if (! $nonce || ! LtiNonce::isValid($nonce)) {
                Log::error('LTI Nonce validation failed', ['nonce' => $nonce]);

                return null;
            }

            // Validate message type
            $messageType = $claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? null;
            if (! in_array($messageType, ['LtiResourceLinkRequest', 'LtiDeepLinkingRequest'])) {
                Log::error('LTI Invalid message type', ['message_type' => $messageType]);

                return null;
            }

            $this->debug('LTI Token validation successful');

            return $claims;
        } catch (\Exception $e) {
            Log::error('LTI Token validation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return null;
        }
    }

    public function createSession(LtiPlatform $platform, array $claims): LtiSession
    {
        return LtiSession::create([
            'lti_platform_id' => $platform->id,
            'lti_user_id' => $claims['sub'] ?? 'unknown',
            'context_id' => $claims['https://purl.imsglobal.org/spec/lti/claim/context']['id'] ?? null,
            'resource_link_id' => $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id'] ?? null,
            'claims' => $claims,
            'session_token' => Str::random(64),
            'expires_at' => now()->addHours((int) config('lti.session_duration', 8)),
        ]);
    }

    public function getSessionByToken(string $token): ?LtiSession
    {
        $session = LtiSession::where('session_token', $token)->first();

        if (! $session || $session->isExpired()) {
            return null;
        }

        return $session;
    }

    public function generateStateToken(): string
    {
        $state = Str::random(32);
        Cache::put("lti_state_{$state}", true, now()->addMinutes(10));

        return $state;
    }

    public function validateStateToken(string $state): bool
    {
        $key = "lti_state_{$state}";
        if (Cache::has($key)) {
            Cache::forget($key);

            return true;
        }

        return false;
    }

    public function generateNonce(): string
    {
        return Str::random(32);
    }

    public function getToolPublicJwks(): array
    {
        $publicKey = file_get_contents(config('lti.public_key_path'));
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => config('lti.key_id'),
                    'n' => rtrim(strtr(base64_encode($keyInfo['rsa']['n']), '+/', '-_'), '='),
                    'e' => rtrim(strtr(base64_encode($keyInfo['rsa']['e']), '+/', '-_'), '='),
                ],
            ],
        ];
    }

    public function createDeepLinkingResponse(LtiPlatform $platform, array $claims, array $items): string
    {
        $privateKey = file_get_contents(config('lti.private_key_path'));

        $payload = [
            'iss' => $platform->client_id,
            'aud' => [$platform->issuer],
            'exp' => now()->addMinutes(5)->timestamp,
            'iat' => now()->timestamp,
            'nonce' => $this->generateNonce(),
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingResponse',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => $claims['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] ?? $platform->deployment_id,
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => $items,
            'https://purl.imsglobal.org/spec/lti-dl/claim/data' => $claims['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']['data'] ?? null,
        ];

        $this->debug('LTI Deep Linking Response', [
            'iss' => $payload['iss'],
            'aud' => $payload['aud'],
            'deployment_id' => $payload['https://purl.imsglobal.org/spec/lti/claim/deployment_id'],
            'items_count' => count($items),
        ]);

        return JWT::encode($payload, $privateKey, 'RS256', config('lti.key_id'));
    }

    private function getPlatformJwks(LtiPlatform $platform): array
    {
        $cacheKey = "lti_jwks_{$platform->id}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $response = Http::timeout(10)->connectTimeout(5)->get($platform->jwks_url);
        $jwks = $response->successful() ? $response->json() : null;

        // A failed or malformed fetch must not be cached for an hour - that
        // would break every launch until the cache expires.
        if (! is_array($jwks) || ! isset($jwks['keys'])) {
            Log::error('LTI JWKS fetch failed', [
                'platform_id' => $platform->id,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException('Could not load platform JWKS.');
        }

        Cache::put($cacheKey, $jwks, now()->addMinutes((int) config('lti.jwks_cache_duration', 60)));

        return $jwks;
    }

    private function objectToArray(mixed $data): mixed
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (is_array($data)) {
            return array_map([$this, 'objectToArray'], $data);
        }

        return $data;
    }
}
