<?php

namespace App\Services;

use App\Models\LtiPlatform;
use App\Models\LtiSession;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends grades back to Moodle (LTI Assignment and Grade Services).
 *
 * Moodle hands each launch the address of its grade column. To write to it,
 * MotionBase first trades a token signed with its own key - the one Moodle
 * already knows from the JWKS endpoint - for a short-lived access token.
 */
class LtiGrades
{
    public const AGS = 'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';

    public const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    public const SCOPE_LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    /**
     * The grade column of this launch: the one Moodle created from the choice
     * made in "Inhalt auswählen", or the one MotionBase created itself.
     */
    public function lineitem(LtiSession $session): ?string
    {
        $endpoint = $session->claims[self::AGS] ?? [];

        if (! empty($endpoint['lineitem']) && in_array(self::SCOPE_SCORE, $endpoint['scope'] ?? [], true)) {
            return $endpoint['lineitem'];
        }

        return LtiContent::binding($session)?->lineitem_url;
    }

    /**
     * Report the learner's grade for this activity. Always the whole total, so
     * a report that failed is made good by the next one.
     */
    public function report(LtiSession $session, int $given, int $maximum, bool $complete): bool
    {
        $lineitem = $this->lineitem($session);

        if (! $lineitem || $maximum < 1) {
            return false;
        }

        $token = $this->accessToken($session->platform, [self::SCOPE_SCORE]);

        if (! $token) {
            return false;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->withHeaders(['Content-Type' => 'application/vnd.ims.lis.v1.score+json'])
            ->withBody(json_encode([
                'userId' => $session->lti_user_id,
                'scoreGiven' => $given,
                'scoreMaximum' => $maximum,
                'activityProgress' => $complete ? 'Completed' : 'InProgress',
                // Anything else and Moodle records the attempt without a grade
                'gradingProgress' => 'FullyGraded',
                'timestamp' => now()->format('Y-m-d\TH:i:s.vP'),
            ]), 'application/vnd.ims.lis.v1.score+json')
            ->post($this->scoresUrl($lineitem));

        // 409: Moodle already holds a grade from the same second. The total
        // was the same moments ago, so there is nothing lost.
        if ($response->successful() || $response->status() === 409) {
            return true;
        }

        Log::warning('LTI score not accepted', [
            'platform_id' => $session->lti_platform_id,
            'status' => $response->status(),
        ]);

        return false;
    }

    /**
     * Create a grade column for an activity whose content was chosen after the
     * fact, when Moodle allows the tool to manage columns.
     */
    public function createLineitem(LtiSession $session, string $label, int $maximum, string $resourceId): ?string
    {
        $endpoint = $session->claims[self::AGS] ?? [];

        if (empty($endpoint['lineitems']) || ! in_array(self::SCOPE_LINEITEM, $endpoint['scope'] ?? [], true)) {
            return null;
        }

        $token = $this->accessToken($session->platform, [self::SCOPE_LINEITEM]);

        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->withBody(json_encode([
                'label' => $label,
                'scoreMaximum' => $maximum,
                'resourceLinkId' => $session->resource_link_id,
                'resourceId' => $resourceId,
            ]), 'application/vnd.ims.lis.v2.lineitem+json')
            ->post($endpoint['lineitems']);

        if (! $response->successful() || empty($response->json('id'))) {
            Log::warning('LTI line item not created', [
                'platform_id' => $session->lti_platform_id,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json('id');
    }

    private function accessToken(LtiPlatform $platform, array $scopes): ?string
    {
        sort($scopes);
        $key = 'lti_ags_token_'.$platform->id.'_'.md5(implode(' ', $scopes));

        if ($token = Cache::get($key)) {
            return $token;
        }

        $assertion = JWT::encode([
            'iss' => $platform->client_id,
            'sub' => $platform->client_id,
            'aud' => $platform->auth_token_url,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp,
            'jti' => (string) Str::uuid(),
        ], file_get_contents(config('lti.private_key_path')), 'RS256', config('lti.key_id'));

        $response = Http::asForm()->timeout(10)->post($platform->auth_token_url, [
            'grant_type' => 'client_credentials',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $assertion,
            'scope' => implode(' ', $scopes),
        ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! $token) {
            Log::warning('LTI access token refused', [
                'platform_id' => $platform->id,
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            return null;
        }

        // A little short of the stated lifetime, so a token never expires mid-request
        Cache::put($key, $token, now()->addSeconds(max(30, (int) $response->json('expires_in', 3600) - 60)));

        return $token;
    }

    /** ".../lineitem?type_id=1" becomes ".../lineitem/scores?type_id=1". */
    private function scoresUrl(string $lineitem): string
    {
        $parts = explode('?', $lineitem, 2);

        return rtrim($parts[0], '/').'/scores'.(isset($parts[1]) ? '?'.$parts[1] : '');
    }
}
