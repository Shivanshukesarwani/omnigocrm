<?php
namespace App\Support;

use App\Models\DeviceToken;
use App\Models\User;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Http;
use Throwable;

class FcmService
{
    public function enabled(): bool
    {
        return (bool) env('FCM_PROJECT_ID')
            && (bool) env('GOOGLE_APPLICATION_CREDENTIALS')
            && is_file(env('GOOGLE_APPLICATION_CREDENTIALS'));
    }

    public function sendToUser(User $user, string $title, string $message, array $data = []): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        $tokens = DeviceToken::withoutGlobalScopes()
            ->where('workspace_id', $user->workspace_id)
            ->where('user_id', $user->id)
            ->get();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $credentials = ApplicationDefaultCredentials::getCredentials([
            'https://www.googleapis.com/auth/firebase.messaging',
        ]);
        $auth = $credentials->fetchAuthToken();
        $accessToken = $auth['access_token'] ?? null;

        if (!$accessToken) {
            return 0;
        }

        $sent = 0;

        foreach ($tokens as $device) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post(
                        'https://fcm.googleapis.com/v1/projects/'
                        . rawurlencode((string) env('FCM_PROJECT_ID'))
                        . '/messages:send',
                        [
                            'message' => [
                                'token' => $device->token,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $message,
                                ],
                                'data' => array_map('strval', $data),
                                'android' => [
                                    'priority' => 'high',
                                    'notification' => [
                                        'channel_id' => 'crm_notifications',
                                    ],
                                ],
                            ],
                        ]
                    );

                if ($response->successful()) {
                    $device->update(['last_used_at' => now()]);
                    $sent++;
                } elseif ($response->status() === 404 || $response->status() === 410) {
                    $device->delete();
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
