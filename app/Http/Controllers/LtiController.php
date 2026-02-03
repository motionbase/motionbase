<?php

namespace App\Http\Controllers;

use App\Models\LtiPlatform;
use App\Models\Topic;
use App\Services\LtiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LtiController extends Controller
{
    public function __construct(
        private LtiService $ltiService
    ) {}

    /**
     * OIDC Login Initiation - Step 1 of LTI 1.3 launch
     */
    public function login(Request $request)
    {
        \Log::info('LTI Login Request', [
            'all_params' => $request->all(),
            'iss' => $request->input('iss'),
            'client_id' => $request->input('client_id'),
            'login_hint' => $request->input('login_hint'),
            'target_link_uri' => $request->input('target_link_uri'),
        ]);

        $request->validate([
            'iss' => 'required|string',
            'login_hint' => 'required|string',
            'target_link_uri' => 'required|url',
            'client_id' => 'required|string',
        ]);

        $platform = $this->ltiService->findPlatformByIssuer(
            $request->input('iss'),
            $request->input('client_id')
        );

        if (! $platform) {
            \Log::error('LTI Platform not found', [
                'iss' => $request->input('iss'),
                'client_id' => $request->input('client_id'),
            ]);
            abort(403, 'Unknown LTI platform');
        }

        $state = $this->ltiService->generateStateToken();
        $nonce = $this->ltiService->generateNonce();

        // Store nonce for validation
        session(['lti_nonce' => $nonce]);

        // Build OIDC auth request
        $params = [
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $platform->client_id,
            'redirect_uri' => route('lti.launch'),
            'login_hint' => $request->input('login_hint'),
            'state' => $state,
            'response_mode' => 'form_post',
            'nonce' => $nonce,
            'prompt' => 'none',
        ];

        if ($request->has('lti_message_hint')) {
            $params['lti_message_hint'] = $request->input('lti_message_hint');
        }

        $authUrl = $platform->auth_login_url.'?'.http_build_query($params);

        return redirect($authUrl);
    }

    /**
     * LTI Launch - Step 2 of LTI 1.3 launch
     */
    public function launch(Request $request)
    {
        // Handle GET request (Moodle validation or direct access)
        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'ok',
                'message' => 'MotionBase LTI 1.3 Tool - Launch endpoint ready',
                'supported_methods' => ['POST'],
            ]);
        }

        $request->validate([
            'id_token' => 'required|string',
            'state' => 'required|string',
        ]);

        // Validate state
        if (! $this->ltiService->validateStateToken($request->input('state'))) {
            abort(403, 'Invalid state token');
        }

        // Decode token header to get issuer
        $tokenParts = explode('.', $request->input('id_token'));
        if (count($tokenParts) !== 3) {
            abort(400, 'Invalid token format');
        }

        $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
        $issuer = $payload['iss'] ?? null;
        $clientId = $payload['aud'] ?? null;

        if (is_array($clientId)) {
            $clientId = $clientId[0];
        }

        $platform = $this->ltiService->findPlatformByIssuer($issuer, $clientId);
        if (! $platform) {
            abort(403, 'Unknown LTI platform');
        }

        // Validate the token
        $claims = $this->ltiService->validateIdToken($request->input('id_token'), $platform);
        if (! $claims) {
            abort(403, 'Invalid LTI token');
        }

        // Create session
        $session = $this->ltiService->createSession($platform, $claims);

        // Determine message type and handle accordingly
        $messageType = $claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? null;

        if ($messageType === 'LtiDeepLinkingRequest') {
            return $this->handleDeepLinking($session, $claims);
        }

        // Handle resource link request
        return $this->handleResourceLink($session, $claims);
    }

    /**
     * Handle Deep Linking request - content selection
     */
    private function handleDeepLinking($session, array $claims)
    {
        $topics = Topic::with(['chapters' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')
            ->with(['sections' => fn ($sq) => $sq->where('is_published', true)->orderBy('sort_order')]),
        ])->get();

        return View::make('lti.deep-linking', [
            'session' => $session,
            'claims' => $claims,
            'topics' => $topics,
            'returnUrl' => $claims['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']['deep_link_return_url'] ?? null,
        ]);
    }

    /**
     * Handle Resource Link request - display content
     */
    private function handleResourceLink($session, array $claims)
    {
        // Get custom parameters to determine what to show
        $custom = $claims['https://purl.imsglobal.org/spec/lti/claim/custom'] ?? [];
        $contentType = $custom['content_type'] ?? 'topic';
        $contentId = $custom['content_id'] ?? null;
        $topicSlug = $custom['topic_slug'] ?? null;
        $sectionSlug = $custom['section_slug'] ?? null;

        // Redirect to embedded view with session token
        $params = ['lti_session' => $session->session_token];

        if ($contentType === 'section' && $topicSlug && $sectionSlug) {
            return redirect()->route('lti.embed.section', array_merge($params, [
                'topic' => $topicSlug,
                'section' => $sectionSlug,
            ]));
        }

        if ($contentType === 'chat' && $topicSlug) {
            return redirect()->route('lti.embed.chat', array_merge($params, [
                'topic' => $topicSlug,
            ]));
        }

        if ($topicSlug) {
            return redirect()->route('lti.embed.topic', array_merge($params, [
                'topic' => $topicSlug,
            ]));
        }

        // Fallback: show content picker
        return redirect()->route('lti.embed.picker', $params);
    }

    /**
     * Process Deep Linking selection and return to platform
     */
    public function deepLinkingReturn(Request $request)
    {
        $request->validate([
            'lti_session' => 'required|string',
            'selected' => 'required|array',
        ]);

        $session = $this->ltiService->getSessionByToken($request->input('lti_session'));
        if (! $session) {
            abort(403, 'Invalid session');
        }

        $platform = $session->platform;
        $claims = $session->claims;

        // Build content items
        $items = [];
        foreach ($request->input('selected') as $selection) {
            $type = $selection['type'] ?? 'topic';
            $url = match ($type) {
                'section' => route('lti.embed.section', [
                    'topic' => $selection['topic_slug'],
                    'section' => $selection['section_slug'],
                ]),
                'chat' => route('lti.embed.chat', [
                    'topic' => $selection['topic_slug'],
                ]),
                default => route('lti.embed.topic', [
                    'topic' => $selection['topic_slug'],
                ]),
            };

            $items[] = [
                'type' => 'ltiResourceLink',
                'title' => $selection['title'],
                'url' => $url,
                'custom' => [
                    'content_type' => $type,
                    'topic_slug' => $selection['topic_slug'],
                    'section_slug' => $selection['section_slug'] ?? null,
                ],
            ];
        }

        // Generate JWT response
        $jwt = $this->ltiService->createDeepLinkingResponse($platform, $claims, $items);

        $returnUrl = $claims['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']['deep_link_return_url'] ?? null;

        return View::make('lti.deep-linking-return', [
            'returnUrl' => $returnUrl,
            'jwt' => $jwt,
        ]);
    }

    /**
     * JWKS endpoint - public keys for token verification
     */
    public function jwks()
    {
        return response()->json($this->ltiService->getToolPublicJwks());
    }
}
