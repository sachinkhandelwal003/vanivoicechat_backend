<?php

namespace App\Services;

use App\Services\Agora\RtcTokenBuilder2;

class AgoraTokenService
{
    public function generateRtcToken(string $channelName, int $uid, ?int $expireSeconds = null): string
    {
        // $appId = env('AGORA_APP_ID');
        // $appCertificate = env('AGORA_APP_CERTIFICATE');
        // $expireSeconds = (int) env('AGORA_TOKEN_EXPIRE', 3600);

        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');
        $expireSeconds = (int) config('services.agora.token_expire', 3600);

        $role = RtcTokenBuilder2::ROLE_PUBLISHER;

        return RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $expireSeconds,
            $expireSeconds
        );
    }
}
