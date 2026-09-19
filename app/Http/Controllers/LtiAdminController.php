<?php

namespace App\Http\Controllers;

use App\Models\LtiPlatform;
use FilesystemIterator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class LtiAdminController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $platforms = LtiPlatform::orderBy('name')->get();

        return Inertia::render('lti/index', [
            'platforms' => $platforms,
            'toolConfig' => $this->getToolConfiguration(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

        $platform->delete();

        return back()->with('success', 'LTI-Plattform gelöscht.');
    }

    /**
     * The MotionBase plugin for Moodle, as the zip Moodle's "Install plugin"
     * page takes - with this MotionBase's address already set, so there is
     * nothing to type in after installing.
     */
    public function moodlePlugin(): BinaryFileResponse
    {
        $this->authorizeAdmin();

        $source = base_path('moodle-plugin/motionbase');
        $path = tempnam(sys_get_temp_dir(), 'motionbase-moodle');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $name = 'motionbase/'.str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
            $contents = file_get_contents($file->getPathname());

            if ($name === 'motionbase/settings.php') {
                $contents = str_replace("'https://motionbase.ch'", var_export(rtrim(config('app.url'), '/'), true), $contents);
            }

            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return response()->download($path, 'moodle-local_motionbase.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /** Platforms decide who may read content through the Moodle plugin. */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
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
