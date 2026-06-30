<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class FirebaseService
{
    protected $client;
    protected $projectId;
    protected $credentials;

    public function __construct()
    {
        $this->client = new Client();

        $jsonFile = base_path(env('FIREBASE_CREDENTIALS'));

        $this->credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $jsonFile
        );

        $json = json_decode(file_get_contents($jsonFile), true);

        // safer than env
        $this->projectId = $json['project_id'];
    }

    public function sendNotification($fcmToken, $title, $body, $image = null)
    {
        try {

            if (empty($fcmToken)) {
                return false;
            }

            $authToken = $this->credentials->fetchAuthToken();

            $accessToken = $authToken['access_token'];

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body"  => $body
                ]
            ];

            // attach image only if valid URL
            if (!empty($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                $message["notification"]["image"] = $image;
            }

            $payload = [
                "message" => $message
            ];

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {

            \Log::error("Firebase Error: " . $e->getMessage());

            return false;
        }
    }
}
