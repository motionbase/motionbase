<?php

namespace App\Services;

use App\Models\LtiNonce;
use App\Models\LtiPlatform;
use App\Models\LtiSession;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LtiService
{
    public function findPlatformByIssuer(string $issuer, string $clientId): ?LtiPlatform
    {
        // Normalize issuer by removing trailing slash
        $normalizedIssuer = rtrim($issuer, '/');

        \Log::info('LTI Platform Lookup', [
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

        \Log::info('LTI Platform Lookup Result', [
            'found' => $platform ? true : false,
            'platform_id' => $platform?->id,
        ]);

        return $platform;
    }

    public function validateIdToken(string $idToken, LtiPlatform $platform): ?array
    {
        try {
            \Log::info('LTI Token Validation Start', ['platform_id' => $platform->id]);

            $jwks = $this->getPlatformJwks($platform);
            \Log::info('LTI JWKS fetched', ['jwks_keys_count' => count($jwks['keys'] ?? [])]);

            $decoded = JWT::decode($idToken, JWK::parseKeySet($jwks));
            $claims = $this->objectToArray($decoded);

            \Log::info('LTI Token decoded successfully', [
                'iss' => $claims['iss'] ?? 'missing',
                'aud' => $claims['aud'] ?? 'missing',
                'message_type' => $claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? 'missing',
            ]);

            // Validate required claims - use flexible issuer matching
            $tokenIssuer = $claims['iss'] ?? null;
            $normalizedTokenIssuer = rtrim($tokenIssuer, '/');
            $normalizedPlatformIssuer = rtrim($platform->issuer, '/');

            if ($normalizedTokenIssuer !== $normalizedPlatformIssuer) {
                \Log::error('LTI Issuer mismatch', [
                    'token_issuer' => $tokenIssuer,
                    'platform_issuer' => $platform->issuer,
                ]);

                return null;
            }

            if (($claims['aud'] ?? null) !== $platform->client_id) {
                // aud can be array
                $aud = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
                if (! in_array($platform->client_id, $aud)) {
                    \Log::error('LTI Audience mismatch', [
                        'token_aud' => $claims['aud'] ?? 'missing',
                        'platform_client_id' => $platform->client_id,
                    ]);

                    return null;
                }
            }

            // Validate nonce
            $nonce = $claims['nonce'] ?? null;
            if (! $nonce || ! LtiNonce::isValid($nonce)) {
                \Log::error('LTI Nonce validation failed', ['nonce' => $nonce]);

                return null;
            }

            // Validate message type
            $messageType = $claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? null;
            if (! in_array($messageType, ['LtiResourceLinkRequest', 'LtiDeepLinkingRequest'])) {
                \Log::error('LTI Invalid message type', ['message_type' => $messageType]);

                return null;
            }

            \Log::info('LTI Token validation successful');

            return $claims;
        } catch (\Exception $e) {
            \Log::error('LTI Token validation exception', [
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
            'expires_at' => now()->addHours(8),
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
        $publicKey = file_get_contents(storage_path('lti/public.pem'));
        $keyInfo = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => config('app.lti.key_id', 'motionbase-lti-key'),
                    'n' => rtrim(strtr(base64_encode($keyInfo['rsa']['n']), '+/', '-_'), '='),
                    'e' => rtrim(strtr(base64_encode($keyInfo['rsa']['e']), '+/', '-_'), '='),
                ],
            ],
        ];
    }

    public function createDeepLinkingResponse(LtiPlatform $platform, array $claims, array $items): string
    {
        $privateKey = file_get_contents(storage_path('lti/private.pem'));

        $payload = [
            'iss' => config('app.url'),
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

        \Log::info('LTI Deep Linking Response', [
            'iss' => $payload['iss'],
            'aud' => $payload['aud'],
            'deployment_id' => $payload['https://purl.imsglobal.org/spec/lti/claim/deployment_id'],
            'items_count' => count($items),
        ]);

        return JWT::encode($payload, $privateKey, 'RS256', config('app.lti.key_id', 'motionbase-lti-key'));
    }

    private function getPlatformJwks(LtiPlatform $platform): array
    {
        $cacheKey = "lti_jwks_{$platform->id}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($platform) {
            $response = Http::get($platform->jwks_url);

            return $response->json();
        });
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
