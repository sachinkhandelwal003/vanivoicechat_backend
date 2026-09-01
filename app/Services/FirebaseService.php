<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class FirebaseService
{
    protected $client;
    protected $projectId;
    protected $credentials;
    protected $accessToken;

    public function __construct()
    {
        $this->client = new Client();

        // $jsonFile = base_path(env('FIREBASE_CREDENTIALS'));
        $jsonFile = base_path(config('services.firebase.credentials'));

        $this->credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $jsonFile
        );

        $json = json_decode(file_get_contents($jsonFile), true);

        $this->projectId = $json['project_id'];

        // fetch token only once
        $authToken = $this->credentials->fetchAuthToken();

        $this->accessToken = $authToken['access_token'];
    }

    public function sendNotification($fcmToken, $title, $body, $image = null)
    {
        try {

            if (empty($fcmToken)) {
                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $message = [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body"  => $body
                ]
            ];

            if (!empty($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                $message["notification"]["image"] = $image;
            }

            $payload = [
                "message" => $message
            ];

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 10
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {

            \Log::error("Firebase Error: " . $e->getMessage());

            return false;
        }
    }
}
