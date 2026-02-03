<?php

namespace App\Http\Controllers;

use App\Models\LtiPlatform;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LtiAdminController extends Controller
{
    public function index()
    {
        $platforms = LtiPlatform::orderBy('name')->get();

        return Inertia::render('lti/index', [
            'platforms' => $platforms,
            'toolConfig' => $this->getToolConfiguration(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'issuer' => 'required|url|unique:lti_platforms,issuer',
            'client_id' => 'required|string|max:255',
            'deployment_id' => 'nullable|string|max:255',
            'auth_login_url' => 'required|url',
            'auth_token_url' => 'required|url',
            'jwks_url' => 'required|url',
        ]);

        LtiPlatform::create($validated);

        return back()->with('success', 'LTI-Plattform hinzugefügt.');
    }

    public function update(Request $request, LtiPlatform $platform)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'issuer' => 'required|url|unique:lti_platforms,issuer,'.$platform->id,
            'client_id' => 'required|string|max:255',
            'deployment_id' => 'nullable|string|max:255',
            'auth_login_url' => 'required|url',
            'auth_token_url' => 'required|url',
            'jwks_url' => 'required|url',
            'is_active' => 'boolean',
        ]);

        $platform->update($validated);

        return back()->with('success', 'LTI-Plattform aktualisiert.');
    }

    public function destroy(LtiPlatform $platform)
    {
        $platform->delete();

        return back()->with('success', 'LTI-Plattform gelöscht.');
    }

    private function getToolConfiguration(): array
    {
        return [
            'name' => config('app.name'),
            'description' => 'MotionBase LTI 1.3 Tool',
            'target_link_uri' => route('lti.launch'),
            'oidc_initiation_url' => route('lti.login'),
            'jwks_url' => route('lti.jwks'),
            'deep_linking' => [
                'supported' => true,
            ],
            'claims' => [
                'iss',
                'sub',
                'name',
                'given_name',
                'family_name',
                'email',
            ],
            'messages' => [
                [
                    'type' => 'LtiResourceLinkRequest',
                    'target_link_uri' => route('lti.launch'),
                ],
                [
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => route('lti.launch'),
                ],
            ],
        ];
    }
}
