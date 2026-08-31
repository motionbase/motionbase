<?php

use App\Mcp\Servers\MotionBaseServer;
use Laravel\Mcp\Facades\Mcp;

// The MCP endpoint itself. Bearer tokens are issued through the OAuth flow
// below, so claude.ai can connect as a specific MotionBase user.
Mcp::web('mcp', MotionBaseServer::class)
    ->middleware('auth:api');

// Discovery (RFC 8414 / RFC 9728) plus dynamic client registration (RFC 7591),
// wired to Passport's authorize and token endpoints. claude.ai registers itself
// through these, so no client has to be created by hand.
Mcp::oauthRoutes();
