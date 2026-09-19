<?php

namespace App\Http\Controllers;

use App\Models\LtiResourceLink;
use App\Services\LtiContent;
use App\Services\LtiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // login_hint identifies the Moodle user, so the raw payload only goes
        // to the log while LTI_DEBUG is on.
        $this->ltiService->debug('LTI Login Request', ['all_params' => $request->all()]);

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
            Log::error('LTI Platform not found', [
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
            Log::error('LTI Token validation failed in launch');
            abort(403, 'Invalid LTI token');
        }

        $this->ltiService->debug('LTI Launch successful, creating session');

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
     * Handle Deep Linking request - the teacher picks content in Moodle's
     * "Inhalt auswählen" dialog.
     */
    private function handleDeepLinking($session, array $claims)
    {
        return View::make('lti.deep-linking', [
            'session' => $session,
            'catalog' => LtiContent::catalog(),
        ]);
    }

    /**
     * Handle Resource Link request - show what the activity was set up with.
     */
    private function handleResourceLink($session, array $claims)
    {
        if ($content = LtiContent::forSession($session)) {
            return redirect($content->url($session->session_token));
        }

        // Saved without "Inhalt auswählen": the teacher gets to choose right
        // here, the class gets a message instead of a picker it cannot use.
        if ($this->ltiService->isInstructor($session)) {
            return redirect()->route('lti.bind', ['lti_session' => $session->session_token]);
        }

        return View::make('lti.waiting');
    }

    /**
     * Process Deep Linking selection and return to platform
     */
    public function deepLinkingReturn(Request $request)
    {
        $request->validate([
            'lti_session' => 'required|string',
            'choice' => 'required|string|max:40',
        ]);

        $session = $this->ltiService->getSessionByToken($request->input('lti_session'));
        $claims = $session?->claims ?? [];

        if (! $session || ($claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? null) !== 'LtiDeepLinkingRequest') {
            Log::error('LTI Deep Linking: Invalid session');
            abort(403, 'Invalid session');
        }

        $content = LtiContent::fromChoice($request->input('choice'));

        if (! $content) {
            return back()->withErrors(['choice' => 'Dieser Inhalt ist nicht mehr verfügbar. Bitte wähle etwas anderes.']);
        }

        $item = [
            'type' => 'ltiResourceLink',
            'title' => $content->title(),
            'text' => $content->summary(),
            'url' => route('lti.launch'),
            'custom' => $content->customParams(),
        ];

        $jwt = $this->ltiService->createDeepLinkingResponse($session->platform, $claims, [$item]);

        return View::make('lti.deep-linking-return', [
            'returnUrl' => $claims['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings']['deep_link_return_url'] ?? null,
            'jwt' => $jwt,
        ]);
    }

    /**
     * A teacher opening an activity that was saved without content.
     */
    public function bind(Request $request)
    {
        $session = $this->instructorSession($request->query('lti_session'));

        return View::make('lti.bind', [
            'session' => $session,
            'catalog' => LtiContent::catalog(),
            'current' => LtiContent::forSession($session)?->choice(),
        ]);
    }

    public function bindStore(Request $request)
    {
        $request->validate([
            'lti_session' => 'required|string',
            'choice' => 'required|string|max:40',
        ]);

        $session = $this->instructorSession($request->input('lti_session'));

        // A link chosen through "Inhalt auswählen" belongs to Moodle - it is
        // changed there, not overridden from here.
        if (! empty($session->claims['https://purl.imsglobal.org/spec/lti/claim/custom']['content_type'])) {
            abort(409, 'Diese Aktivität wird in Moodle über "Inhalt auswählen" geändert.');
        }

        $content = LtiContent::fromChoice($request->input('choice'));

        if (! $content || ! $session->resource_link_id) {
            return back()->withErrors(['choice' => 'Dieser Inhalt ist nicht mehr verfügbar. Bitte wähle etwas anderes.']);
        }

        $link = LtiResourceLink::firstOrNew([
            'lti_platform_id' => $session->lti_platform_id,
            'resource_link_id' => $session->resource_link_id,
        ]);

        $link->fill([
            'content_type' => $content->type,
            'topic_id' => $content->topic->id,
            'chapter_id' => $content->chapter?->id,
            'section_id' => $content->section?->id,
        ]);

        $link->save();

        return redirect($content->url($session->session_token));
    }

    private function instructorSession(?string $token)
    {
        $session = $token ? $this->ltiService->getSessionByToken($token) : null;

        abort_unless($session && $this->ltiService->isInstructor($session), 403, 'Nur Lehrpersonen können den Inhalt wählen.');

        return $session;
    }

    /**
     * JWKS endpoint - public keys for token verification
     */
    public function jwks()
    {
        return response()->json($this->ltiService->getToolPublicJwks());
    }
}
