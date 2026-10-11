<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MicrosoftAuthController extends Controller
{
    public function redirect()
    {
        $state = Str::random(40);

        Cache::put(
            'microsoft_oauth_state_' . $state,
            true,
            now()->addMinutes(10)
        );

        $query = http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.microsoft.redirect_uri'),
            'response_mode' => 'query',
            'scope' => 'openid profile offline_access User.Read Files.ReadWrite',
            'state' => $state,
        ]);

        $tenantId = config('services.microsoft.tenant_id');

        return redirect(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?{$query}"
        );
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return response()->json([
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ], 400);
        }

        $state = $request->input('state');

        if (!$state || !Cache::pull('microsoft_oauth_state_' . $state)) {
            return response()->json([
                'error' => 'Invalid OAuth state.',
            ], 400);
        }

        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'error' => 'Authorization code was not provided.',
            ], 400);
        }

        $tenantId = config('services.microsoft.tenant_id');

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.microsoft.redirect_uri'),
                'scope' => 'openid profile offline_access User.Read Files.ReadWrite',
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'error' => 'Microsoft token request failed.',
                'details' => $response->json(),
            ], 400);
        }

        $tokens = $response->json();

        /*
         * Store tokens temporarily for testing.
         *
         * Later we should store the refresh token
         * securely in the database.
         */
        Cache::put(
            'microsoft_excel_tokens',
            $tokens,
            now()->addSeconds(
                $tokens['expires_in'] ?? 3600
            )
        );

        return response()->json([
            'message' => 'Microsoft authorization successful.',
            'token_type' => $tokens['token_type'] ?? null,
            'expires_in' => $tokens['expires_in'] ?? null,
            'scope' => $tokens['scope'] ?? null,
        ]);
    }

    public function testSharePoint()
    {
        $tokens = Cache::get('microsoft_excel_tokens');

        if (!$tokens || empty($tokens['access_token'])) {
            return response()->json([
                'error' => 'Microsoft authorization required.',
                'authorize_url' => route('microsoft.redirect'),
            ], 401);
        }

        $accessToken = $tokens['access_token'];

        $response = Http::withToken($accessToken)
            ->get('https://graph.microsoft.com/v1.0/sites/nabinaholding.sharepoint.com:/sites/IT');

        return response()->json([
            'status' => $response->status(),
            'data' => $response->json(),
        ]);
    }

    public function testDrive()
    {
        $tokens = Cache::get('microsoft_excel_tokens');

        if (!$tokens || empty($tokens['access_token'])) {
            return response()->json([
                'error' => 'Microsoft authorization required.',
            ], 401);
        }

        $siteId = 'nabinaholding.sharepoint.com,43d1e854-5e63-4736-9958-14ddd47f3baf,2b165257-0e0f-450b-85cb-a3d503dd6ae9';

        $response = Http::withToken($tokens['access_token'])
            ->get(
                "https://graph.microsoft.com/v1.0/sites/{$siteId}/drive"
            );

        return response()->json([
            'status' => $response->status(),
            'data' => $response->json(),
        ]);
    }

    public function testExcelFile()
    {
        $tokens = Cache::get('microsoft_excel_tokens');

        if (!$tokens || empty($tokens['access_token'])) {
            return response()->json([
                'error' => 'Microsoft authorization required.',
            ], 401);
        }

        $siteId = 'nabinaholding.sharepoint.com,43d1e854-5e63-4736-9958-14ddd47f3baf,2b165257-0e0f-450b-85cb-a3d503dd6ae9';

        $response = Http::withToken($tokens['access_token'])
            ->get(
                "https://graph.microsoft.com/v1.0/sites/{$siteId}/drive/root:/Event%20Registrations.xlsx"
            );

        return response()->json([
            'status' => $response->status(),
            'data' => $response->json(),
        ]);
    }

    public function clearMicrosoftToken()
    {
        Cache::forget('microsoft_excel_tokens');

        return response()->json([
            'message' => 'Microsoft token cache cleared.',
        ]);
    }

    public function testMe()
{
    $tokens = Cache::get('microsoft_excel_tokens');

    if (!$tokens || empty($tokens['access_token'])) {
        return response()->json([
            'error' => 'Microsoft authorization required.',
        ], 401);
    }

    $response = Http::withToken($tokens['access_token'])
        ->get('https://graph.microsoft.com/v1.0/me');

    return response()->json([
        'status' => $response->status(),
        'data' => $response->json(),
    ]);
}

public function testSiteRoot()
{
    $tokens = Cache::get('microsoft_excel_tokens');

    if (!$tokens || empty($tokens['access_token'])) {
        return response()->json([
            'error' => 'Microsoft authorization required.',
        ], 401);
    }

    $siteId = 'nabinaholding.sharepoint.com,43d1e854-5e63-4736-9958-14ddd47f3baf,2b165257-0e0f-450b-85cb-a3d503dd6ae9';

    $response = Http::withToken($tokens['access_token'])
        ->get(
            "https://graph.microsoft.com/v1.0/sites/{$siteId}/drive/root/children"
        );

    return response()->json([
        'status' => $response->status(),
        'data' => $response->json(),
    ]);
}
}