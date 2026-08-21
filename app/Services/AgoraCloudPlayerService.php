<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AgoraCloudPlayerService
{
    protected function baseUrl(): string
    {
        // $region = strtolower((string) env('AGORA_REGION'));
        // $appId  = env('AGORA_APP_ID');
        $region = strtolower((string) config('services.agora.region'));
        $appId = config('services.agora.app_id');

        return "https://api.agora.io/{$region}/v1/projects/{$appId}/cloud-player";
    }

    protected function authHeader(): string
    {
        // $customerId     = env('AGORA_CUSTOMER_ID');
        // $customerSecret = env('AGORA_CUSTOMER_SECRET');
        $customerId     = config('services.agora.customer_id');
        $customerSecret = config('services.agora.customer_secret');



        return 'Basic ' . base64_encode($customerId . ':' . $customerSecret);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => $this->authHeader(),
            'Content-Type'  => 'application/json',
            'X-Request-ID'  => (string) Str::uuid(),
        ];
    }

    public function createPlayer(array $payload): array
    {
        $response = Http::withHeaders($this->defaultHeaders())
            ->timeout(25)
            ->post($this->baseUrl() . '/players', $payload);

        $json = $response->json();

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $json,
            'body'        => $response->body(),
            'player_id'   => $json['player']['id'] ?? $response->header('X-Resource-ID'),
            'player_uid'  => $json['player']['uid'] ?? null,
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function getPlayer(string $playerId): array
    {
        try {
            $response = Http::withHeaders($this->defaultHeaders())
                ->timeout(20)
                ->get($this->baseUrl() . '/players/' . $playerId);

            $json = $response->json();

            return [
                'ok'          => $response->successful(),
                'status'      => $response->status(),
                'json'        => $json,
                'body'        => $json ?: $response->body(),
                'player_id'   => $playerId,
                'request_id'  => $response->header('X-Request-ID'),
                'resource_id' => $response->header('X-Resource-ID'),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'         => false,
                'status'     => 500,
                'json'       => null,
                'body'       => $e->getMessage(),
                'player_id'  => $playerId,
                'request_id' => null,
            ];
        }
    }

    public function listPlayers(): array
    {
        $response = Http::withHeaders($this->defaultHeaders())
            ->timeout(20)
            ->get($this->baseUrl() . '/players');

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function pausePlayer(string $playerId, int $sequence): array
    {
        $response = Http::withHeaders($this->defaultHeaders())
            ->timeout(20)
            ->patch(
                $this->baseUrl() . '/players/' . $playerId . '?sequence=' . $sequence,
                [
                    'player' => [
                        'isPause' => true,
                    ],
                ]
            );

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function resumePlayer(string $playerId, int $sequence): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type' => 'application/json',
            'X-Request-ID' => (string) \Illuminate\Support\Str::uuid(),
        ])->timeout(20)->patch(
            $this->baseUrl() . '/players/' . $playerId . '?sequence=' . $sequence,
            [
                'player' => [
                    'isPause' => false,
                ],
            ]
        );

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function seekPlayer(string $playerId, int $sequence, int $seekPosition, bool $isPause = false, ?int $volume = null): array
    {
        $payload = [
            'player' => [
                'seekPosition' => $seekPosition,
                'isPause' => $isPause,
            ],
        ];

        if (!is_null($volume)) {
            $payload['player']['audioOptions'] = [
                'volume' => max(0, min(200, $volume)),
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type' => 'application/json',
            'X-Request-ID' => (string) \Illuminate\Support\Str::uuid(),
        ])->timeout(20)->patch(
            $this->baseUrl() . '/players/' . $playerId . '?sequence=' . $sequence,
            $payload
        );

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function updatePlayerVolume(string $playerId, int $sequence, int $volume, ?bool $isPause = null): array
    {
        $volume = max(0, min(200, $volume));

        $payload = [
            'player' => [
                'audioOptions' => [
                    'volume' => $volume,
                ],
            ],
        ];

        if (!is_null($isPause)) {
            $payload['player']['isPause'] = $isPause;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type' => 'application/json',
            'X-Request-ID' => (string) \Illuminate\Support\Str::uuid(),
        ])->timeout(20)->patch(
            $this->baseUrl() . '/players/' . $playerId . '?sequence=' . $sequence,
            $payload
        );

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }

    public function deletePlayer(string $playerId): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'X-Request-ID'  => (string) Str::uuid(),
        ])
            ->timeout(20)
            ->delete($this->baseUrl() . '/players/' . $playerId);

        return [
            'ok'          => $response->successful(),
            'status'      => $response->status(),
            'json'        => $response->json(),
            'body'        => $response->body(),
            'request_id'  => $response->header('X-Request-ID'),
            'resource_id' => $response->header('X-Resource-ID'),
        ];
    }
}
