@extends('layouts.app')

@section('content')
@php
use Illuminate\Support\Str;

$defaultAvatar = asset('assets/img/team/avatar.png');

$sender = $giftTransaction->sender;
$receiver = $giftTransaction->receiver;
$gift = $giftTransaction->gift;
$room = $giftTransaction->room;
$roomOwner = $room?->user;

$senderImage = $defaultAvatar;
if (!empty($sender?->image)) {
$senderImage = Str::startsWith($sender->image, ['http://', 'https://'])
? $sender->image
: Helper::showImage($sender->image, true);
}

$receiverImage = $defaultAvatar;
if (!empty($receiver?->image)) {
$receiverImage = Str::startsWith($receiver->image, ['http://', 'https://'])
? $receiver->image
: Helper::showImage($receiver->image, true);
}

$roomImage = $defaultAvatar;
if (!empty($room?->room_image)) {
$roomImage = Str::startsWith($room->room_image, ['http://', 'https://'])
? $room->room_image
: Helper::showImage($room->room_image, true);
}

$giftImage = null;
if (!empty($gift?->file_path)) {
$giftImage = Helper::showImage($gift->file_path, true);
} elseif (!empty($gift?->cover)) {
$giftImage = Helper::showImage($gift->cover, true);
}

$giftQty = $giftTransaction->multiplier ?? 1;
$giftPrice = number_format((float)($giftTransaction->coin_value ?? 0), 2);
$totalGiftValue = number_format((float)($giftTransaction->total_value ?? 0), 2);
@endphp

<style>
    .gift-details-page .main-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
    }

    .gift-details-page .page-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border-bottom: 1px solid #edf2f9;
        padding: 18px 22px;
    }

    .gift-details-page .page-title {
        font-size: 20px;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .gift-details-page .section-card {
        background: #fff;
        border: 1px solid #edf2f9;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 18px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
    }

    .gift-details-page .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 700;
        color: #344050;
        margin-bottom: 16px;
    }

    .gift-details-page .section-title::before {
        content: "";
        width: 4px;
        height: 20px;
        background: #2c7be5;
        border-radius: 10px;
        display: inline-block;
    }

    .gift-details-page .profile-block {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .gift-details-page .profile-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #edf2f9;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        background: #fff;
    }

    .gift-details-page .profile-name {
        font-size: 15px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 2px;
    }

    .gift-details-page .profile-sub {
        font-size: 13px;
        color: #748194;
    }

    .gift-details-page .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 24px;
    }

    .gift-details-page .info-item {
        background: #fbfcfe;
        border: 1px solid #edf2f9;
        border-radius: 12px;
        padding: 12px 14px;
        min-height: 64px;
    }

    .gift-details-page .info-label {
        font-size: 12px;
        color: #748194;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .gift-details-page .info-value {
        font-size: 14px;
        color: #2c3e50;
        font-weight: 600;
        word-break: break-word;
    }

    .gift-details-page .gift-cover-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .gift-details-page .gift-cover-box img {
        width: 34px;
        height: 34px;
        object-fit: contain;
    }

    .gift-details-page .soft-badge {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: #e7f1ff;
        color: #2c7be5;
        border: 1px solid #cfe2ff;
        margin-right: 6px;
        margin-bottom: 6px;
    }

    .gift-details-page .receiver-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 14px;
        border-radius: 14px;
        border: 1px solid #edf2f9;
        background: #fbfcfe;
        flex-wrap: wrap;
    }

    .gift-details-page .meta-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .gift-details-page .back-btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 14px;
    }

    @media (max-width: 767px) {
        .gift-details-page .info-grid {
            grid-template-columns: 1fr;
        }

        .gift-details-page .page-header {
            padding: 16px;
        }

        .gift-details-page .section-card {
            padding: 14px;
        }
    }
</style>

<div class="gift-details-page">
    <div class="card main-card mb-3">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="page-title">Gift Transaction Details</h4>
            <a href="{{ route('giftrecords') }}" class="btn back-btn">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card-body p-3 p-md-4">

            {{-- Sender --}}
            <div class="section-card">
                <div class="section-title">Sender</div>

                <div class="profile-block">
                    <img src="{{ $senderImage }}" alt="sender" class="profile-avatar">
                    <div>
                        <div class="profile-name">{{ $sender->name ?? '-' }}</div>
                        <div class="profile-sub">UID: {{ $sender->uid ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Room Information --}}
            <div class="section-card">
                <div class="section-title">Room Information</div>

                <div class="profile-block mb-3">
                    <img src="{{ $roomImage }}" alt="room" class="profile-avatar">
                    <div>
                        <div class="profile-name">{{ $room->room_name ?? '-' }}</div>
                        <div class="profile-sub">Owner UID: {{ $roomOwner->uid ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Room ID</div>
                        <div class="info-value">{{ $room->room_id ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Region</div>
                        <div class="info-value">{{ $room->country ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room Rating</div>
                        <div class="info-value">{{ $room->total_points ?? 0 }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room Members</div>
                        <div class="info-value">{{ $room->active_members_count ?? 0 }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room Administrator</div>
                        <div class="info-value">{{ $room->user_id ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room Status</div>
                        <div class="info-value">
                            <span class="soft-badge">normal</span>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Homeowner ID</div>
                        <div class="info-value">{{ $room->user_id ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Homeowner UID</div>
                        <div class="info-value">{{ $roomOwner->uid ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Creation Time</div>
                        <div class="info-value">{{ optional($giftTransaction->created_at)->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>

            {{-- Gift Information --}}
            <div class="section-card">
                <div class="section-title">Gift Information</div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Gift ID</div>
                        <div class="info-value">{{ $giftTransaction->gift_id ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Gift Cover</div>
                        <div class="info-value">
                            <div class="gift-cover-box">
                                @if($giftImage)
                                <img src="{{ $giftImage }}" alt="gift">
                                @else
                                <span>-</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Gift Name</div>
                        <div class="info-value">{{ $gift->title ?? $gift->name ?? '-' }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Quantity of Gifts</div>
                        <div class="info-value">{{ $giftQty }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Gift Price</div>
                        <div class="info-value">{{ $giftPrice }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Total Gift Value</div>
                        <div class="info-value">{{ $totalGiftValue }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Number of People to Receive</div>
                        <div class="info-value">1</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room ID Reference</div>
                        <div class="info-value">{{ $giftTransaction->room_id ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Receiving Users --}}
            <div class="section-card mb-0">
                <div class="section-title">Receiving Users</div>

                <div class="receiver-card">
                    <div class="profile-block">
                        <img src="{{ $receiverImage }}" alt="receiver" class="profile-avatar">
                        <div>
                            <div class="profile-name">{{ $receiver->name ?? '-' }}</div>
                            <div class="profile-sub">UID: {{ $receiver->uid ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="text-md-end">
                        <div class="meta-inline">
                            <span class="soft-badge">Acceptance rate: 20%</span>
                            <span class="soft-badge">
                                Region (send, receive): {{ $sender->country ?? '-' }}, {{ $receiver->country ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection