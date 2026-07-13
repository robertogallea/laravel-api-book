<?php

namespace App\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class PartnerController extends Controller
{
    public function ping()
    {
        $client = Auth::guard('api')->client();

        // CheckToken (the `client` middleware) validates the token itself, not the client that
        // issued it: a token minted before its client was revoked still passes that check.
        // findActive(), called above, is what actually filters out a revoked client, returning
        // null here. Without this check, that would surface as an uncaught error instead of the
        // same Problem Details response every other authentication failure already produces.
        if (! $client) {
            throw new AuthenticationException;
        }

        return response()->json([
            'data' => [
                'authenticated_as' => $client->name,
            ],
        ]);
    }
}
