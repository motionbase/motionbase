<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // LTI Platforms (Moodle instances)
        Schema::create('lti_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('issuer')->unique(); // Platform issuer URL
            $table->string('client_id');
            $table->string('deployment_id')->nullable();
            $table->text('auth_login_url'); // OIDC login URL
            $table->text('auth_token_url'); // Token endpoint
            $table->text('jwks_url'); // Platform JWKS URL
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // LTI Nonces (replay attack prevention)
        Schema::create('lti_nonces', function (Blueprint $table) {
            $table->id();
            $table->string('nonce');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['nonce', 'expires_at']);
        });

        // LTI Launch Sessions
        Schema::create('lti_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lti_platform_id')->constrained()->onDelete('cascade');
            $table->string('lti_user_id');
            $table->string('context_id')->nullable(); // Course ID in Moodle
            $table->string('resource_link_id')->nullable();
            $table->json('claims')->nullable(); // Full LTI claims
            $table->string('session_token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lti_sessions');
        Schema::dropIfExists('lti_nonces');
        Schema::dropIfExists('lti_platforms');
    }
};
