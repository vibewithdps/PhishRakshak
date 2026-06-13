<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleAccount;
use App\Models\Scan;
use App\Services\ScamDetectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GmailProtectionController extends Controller
{
    private string $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    private string $googleTokenUrl = 'https://oauth2.googleapis.com/token';
    private string $gmailBaseUrl = 'https://gmail.googleapis.com/gmail/v1/users/me';

    public function __construct(private ScamDetectionService $scamDetectionService)
    {
    }

    public function status(Request $request)
    {
        $account = GoogleAccount::where('user_id', $request->user()->id)->first();

        return response()->json([
            'status' => true,
            'data' => [
                'connected' => (bool) $account,
                'google_email' => $account?->google_email,
                'protection_mode' => $account?->protection_mode ?? 'notify',
                'trash_threshold' => $account?->trash_threshold ?? 0.90,
                'scan_limit' => $account?->scan_limit ?? 10,
                'last_scan_at' => optional($account?->last_scan_at)->toDateTimeString(),
                'token_expires_at' => optional($account?->token_expires_at)->toDateTimeString(),
            ],
        ]);
    }

    public function authUrl(Request $request)
    {
        $missing = $this->missingGoogleConfig();

        if (!empty($missing)) {
            return response()->json([
                'status' => false,
                'message' => 'Google OAuth config missing: ' . implode(', ', $missing),
            ], 422);
        }

        $state = $this->makeState($request->user()->id);

        $query = http_build_query([
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/userinfo.email',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'auth_url' => $this->googleAuthUrl . '?' . $query,
            ],
        ]);
    }

    public function callback(Request $request)
    {
        if (!$request->filled('code') || !$request->filled('state')) {
            return $this->redirectToFrontend('gmail=failed&reason=missing_code_or_state');
        }

        $userId = $this->readUserIdFromState($request->state);

        if (!$userId) {
            return $this->redirectToFrontend('gmail=failed&reason=invalid_state');
        }

        $tokenResponse = Http::asForm()->post($this->googleTokenUrl, [
            'code' => $request->code,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            return $this->redirectToFrontend('gmail=failed&reason=token_exchange_failed');
        }

        $tokenData = $tokenResponse->json();
        $accessToken = Arr::get($tokenData, 'access_token');
        $refreshToken = Arr::get($tokenData, 'refresh_token');

        if (!$accessToken) {
            return $this->redirectToFrontend('gmail=failed&reason=no_access_token');
        }

        $email = $this->fetchGoogleEmail($accessToken);

        $existingAccount = GoogleAccount::where('user_id', $userId)->first();

        GoogleAccount::updateOrCreate(
            ['user_id' => $userId],
            [
                'google_email' => $email,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken ?: $existingAccount?->refresh_token,
                'token_expires_at' => now()->addSeconds((int) Arr::get($tokenData, 'expires_in', 3600) - 60),
                'scope' => Arr::get($tokenData, 'scope'),
                'protection_mode' => $existingAccount?->protection_mode ?? 'notify',
                'trash_threshold' => $existingAccount?->trash_threshold ?? 0.90,
                'scan_limit' => $existingAccount?->scan_limit ?? 10,
            ]
        );

        return $this->redirectToFrontend('gmail=connected');
    }

    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'protection_mode' => 'required|in:notify,label,trash_high_risk',
            'trash_threshold' => 'required|numeric|min:0.50|max:0.99',
            'scan_limit' => 'required|integer|min:1|max:25',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $account = GoogleAccount::where('user_id', $request->user()->id)->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Connect Gmail first.',
            ], 404);
        }

        $account->update([
            'protection_mode' => $request->protection_mode,
            'trash_threshold' => $request->trash_threshold,
            'scan_limit' => $request->scan_limit,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Mail protection settings updated.',
            'data' => [
                'protection_mode' => $account->protection_mode,
                'trash_threshold' => $account->trash_threshold,
                'scan_limit' => $account->scan_limit,
            ],
        ]);
    }

    public function scanInbox(Request $request)
    {
        $account = GoogleAccount::where('user_id', $request->user()->id)->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Connect Gmail first.',
            ], 404);
        }

        $accessToken = $this->validAccessToken($account);

        if (!$accessToken) {
            return response()->json([
                'status' => false,
                'message' => 'Google token expired. Please reconnect Gmail.',
            ], 401);
        }

        $limit = min(max((int) $request->input('limit', $account->scan_limit), 1), 25);
        $query = $request->input('query', 'newer_than:30d -from:me');

        $listResponse = Http::withToken($accessToken)->get($this->gmailBaseUrl . '/messages', [
            'maxResults' => $limit,
            'q' => $query,
        ]);

        if (!$listResponse->successful()) {
            return response()->json([
                'status' => false,
                'message' => 'Gmail list failed.',
                'google_error' => $listResponse->json(),
            ], $listResponse->status());
        }

        $messages = $listResponse->json('messages', []);
        $results = [];

        foreach ($messages as $message) {
            $gmailMessageId = Arr::get($message, 'id');

            if (!$gmailMessageId) {
                continue;
            }

            $messageDetails = $this->fetchGmailMessage($accessToken, $gmailMessageId);

            if (!$messageDetails) {
                continue;
            }

            $scanText = $this->buildScanText($messageDetails);
            $detection = $this->scamDetectionService->detect($scanText, 'email');
            $action = $this->applyGmailAction($accessToken, $account, $gmailMessageId, $detection);

            $scan = Scan::create([
                'user_id' => $request->user()->id,
                'type' => 'email',
                'content' => Str::limit($scanText, 1200),
                'is_phishing' => $detection['is_phishing'],
                'confidence' => $detection['confidence'],
                'category' => $detection['category'],
                'explanation' => $detection['explanation'] . ' Gmail action: ' . $action,
            ]);

            $results[] = [
                'gmail_message_id' => $gmailMessageId,
                'from' => $this->headerValue($messageDetails, 'From'),
                'subject' => $this->headerValue($messageDetails, 'Subject'),
                'snippet' => Arr::get($messageDetails, 'snippet'),
                'is_phishing' => $detection['is_phishing'],
                'confidence' => $detection['confidence'],
                'category' => $detection['category'],
                'explanation' => $detection['explanation'],
                'action' => $action,
                'scan_id' => $scan->id,
            ];
        }

        $account->update([
            'last_scan_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Gmail scan completed.',
            'data' => $results,
        ]);
    }

    public function disconnect(Request $request)
    {
        GoogleAccount::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Gmail disconnected from PhishRakshak.',
        ]);
    }

    private function missingGoogleConfig(): array
    {
        $missing = [];

        foreach (['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI'] as $key) {
            if (!env($key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private function makeState(int $userId): string
    {
        $payload = $userId . '|' . Str::random(32) . '|' . now()->addMinutes(15)->timestamp;
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
    }

    private function readUserIdFromState(string $state): ?int
    {
        $decoded = base64_decode(strtr($state, '-_', '+/'));

        if (!$decoded) {
            return null;
        }

        $parts = explode('|', $decoded);

        if (count($parts) !== 4) {
            return null;
        }

        [$userId, $nonce, $expiresAt, $signature] = $parts;
        $payload = $userId . '|' . $nonce . '|' . $expiresAt;
        $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        if (Carbon::createFromTimestamp((int) $expiresAt)->isPast()) {
            return null;
        }

        return (int) $userId;
    }

    private function redirectToFrontend(string $query)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return redirect()->away($frontendUrl . '/protection?' . $query);
    }

    private function fetchGoogleEmail(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (!$response->successful()) {
            return null;
        }

        return $response->json('email');
    }

    private function validAccessToken(GoogleAccount $account): ?string
    {
        if ($account->token_expires_at && $account->token_expires_at->isFuture()) {
            return $account->access_token;
        }

        if (!$account->refresh_token) {
            return null;
        }

        $response = Http::asForm()->post($this->googleTokenUrl, [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $tokenData = $response->json();
        $accessToken = Arr::get($tokenData, 'access_token');

        if (!$accessToken) {
            return null;
        }

        $account->update([
            'access_token' => $accessToken,
            'token_expires_at' => now()->addSeconds((int) Arr::get($tokenData, 'expires_in', 3600) - 60),
            'scope' => Arr::get($tokenData, 'scope', $account->scope),
        ]);

        return $accessToken;
    }

    private function fetchGmailMessage(string $accessToken, string $gmailMessageId): ?array
    {
        $response = Http::withToken($accessToken)->get($this->gmailBaseUrl . '/messages/' . $gmailMessageId, [
            'format' => 'metadata',
            'metadataHeaders' => ['From', 'Reply-To', 'Subject', 'To', 'Date'],
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function buildScanText(array $messageDetails): string
    {
        return implode("\n", array_filter([
            'From: ' . $this->headerValue($messageDetails, 'From'),
            'Reply-To: ' . $this->headerValue($messageDetails, 'Reply-To'),
            'Subject: ' . $this->headerValue($messageDetails, 'Subject'),
            'Snippet: ' . Arr::get($messageDetails, 'snippet'),
        ]));
    }

    private function headerValue(array $messageDetails, string $headerName): ?string
    {
        $headers = Arr::get($messageDetails, 'payload.headers', []);

        foreach ($headers as $header) {
            if (strtolower(Arr::get($header, 'name')) === strtolower($headerName)) {
                return Arr::get($header, 'value');
            }
        }

        return null;
    }

    private function applyGmailAction(string $accessToken, GoogleAccount $account, string $gmailMessageId, array $detection): string
    {
        if (!$detection['is_phishing']) {
            return 'no_action_safe';
        }

        if ($account->protection_mode === 'notify') {
            return 'notify_only';
        }

        if ($account->protection_mode === 'trash_high_risk' && $detection['confidence'] >= $account->trash_threshold) {
            $trashResponse = Http::withToken($accessToken)->post($this->gmailBaseUrl . '/messages/' . $gmailMessageId . '/trash');

            return $trashResponse->successful() ? 'moved_to_gmail_trash' : 'trash_failed';
        }

        return $this->applyPhishRakshakLabel($accessToken, $gmailMessageId);
    }

    private function applyPhishRakshakLabel(string $accessToken, string $gmailMessageId): string
    {
        $labelId = $this->findOrCreateLabel($accessToken, 'PhishRakshak-Spam');

        if (!$labelId) {
            return 'label_failed';
        }

        $response = Http::withToken($accessToken)->post($this->gmailBaseUrl . '/messages/' . $gmailMessageId . '/modify', [
            'addLabelIds' => [$labelId],
        ]);

        return $response->successful() ? 'labeled_phishrakshak_spam' : 'label_failed';
    }

    private function findOrCreateLabel(string $accessToken, string $labelName): ?string
    {
        $listResponse = Http::withToken($accessToken)->get($this->gmailBaseUrl . '/labels');

        if ($listResponse->successful()) {
            foreach ($listResponse->json('labels', []) as $label) {
                if (Arr::get($label, 'name') === $labelName) {
                    return Arr::get($label, 'id');
                }
            }
        }

        $createResponse = Http::withToken($accessToken)->post($this->gmailBaseUrl . '/labels', [
            'name' => $labelName,
            'labelListVisibility' => 'labelShow',
            'messageListVisibility' => 'show',
        ]);

        if (!$createResponse->successful()) {
            return null;
        }

        return $createResponse->json('id');
    }
}
