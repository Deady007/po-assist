<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;

class DriveAuthController extends Controller
{
    public function start(Request $request)
    {
        $client = $this->buildClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $error = $request->query('error');
        if ($error) {
            return view('drive.oauth-callback', [
                'error' => $error,
                'refreshToken' => null,
                'token' => null,
            ]);
        }

        $code = $request->query('code');
        if (!$code) {
            return view('drive.oauth-callback', [
                'error' => 'Authorization code missing.',
                'refreshToken' => null,
                'token' => null,
            ]);
        }

        $client = $this->buildClient();
        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Throwable $e) {
            return view('drive.oauth-callback', [
                'error' => 'Failed to exchange code: ' . $e->getMessage(),
                'refreshToken' => null,
                'token' => null,
            ]);
        }

        if (isset($token['error'])) {
            return view('drive.oauth-callback', [
                'error' => $token['error_description'] ?? $token['error'],
                'refreshToken' => null,
                'token' => null,
            ]);
        }

        $refreshToken = $token['refresh_token'] ?? $client->getRefreshToken() ?? null;

        return view('drive.oauth-callback', [
            'error' => null,
            'refreshToken' => $refreshToken,
            'token' => $token,
        ]);
    }

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setApplicationName(config('services.google_drive.app_name', 'PO-Assist'));
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->setRedirectUri(config('services.google_drive.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([config('services.google_drive.scope', Drive::DRIVE_FILE)]);

        return $client;
    }
}
