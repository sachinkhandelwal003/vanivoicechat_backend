@extends('layouts.app')

@section('content')
@php
use Illuminate\Support\Str;

$defaultAvatar = asset('assets/img/team/avatar.png');

$userImage = $defaultAvatar;
if (!empty($post->user?->image)) {
$userImage = Str::startsWith($post->user->image, ['http://', 'https://'])
? $post->user->image
: Helper::showImage($post->user->image, true);
}

$postType = 'text';
if ($post->description && $post->media->count()) {
$postType = 'text + picture';
} elseif ($post->media->count()) {
$first = $post->media->first();
$postType = Str::startsWith($first->file_type, 'video/') ? 'video' : 'picture';
}
@endphp

<style>
    .post-details {
        max-width: 900px;
        margin: auto;
    }

    .post-card {
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        border: none;
    }

    .user-box {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #eee;
    }

    .section-title {
        font-weight: 700;
        font-size: 15px;
        margin-bottom: 12px;
        border-left: 4px solid #2c7be5;
        padding-left: 10px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .info-box {
        background: #f8fbff;
        border: 1px solid #edf2f9;
        padding: 12px;
        border-radius: 10px;
    }

    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .media-grid img,
    .media-grid video {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 12px;
    }

    .badge-soft {
        background: #e7f1ff;
        color: #2c7be5;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<div class="post-details">
    <div class="card post-card mb-3">

        <div class="card-header d-flex justify-content-between">
            <h5>Post Details</h5>
            <a href="{{ route('posts.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Back
            </a>
        </div>

        <div class="card-body">

            {{-- USER --}}
            <div class="mb-4">
                <div class="section-title">User</div>

                <div class="user-box">
                    <img src="{{ $userImage }}" class="user-avatar">
                    <div>
                        <div class="fw-bold">{{ $post->user->name ?? '-' }}</div>
                        <div class="text-muted small">UID: {{ $post->user->uid ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- POST INFO --}}
            <div class="mb-4">
                <div class="section-title">Post Information</div>

                <div class="info-grid">
                    <div class="info-box">
                        <small class="text-muted">Post ID</small>
                        <div>{{ $post->id }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Type</small>
                        <div>{{ $postType }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Topic</small>
                        <div>{{ $post->topic->name ?? '-' }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Country</small>
                        <div>{{ $post->country ?? '-' }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Likes</small>
                        <div>{{ $post->likes_count ?? 0 }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Comments</small>
                        <div>{{ $post->comments_count ?? 0 }}</div>
                    </div>

                    <div class="info-box">
                        <small class="text-muted">Created At</small>
                        <div>{{ optional($post->created_at)->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            </div>

            {{-- DESCRIPTION --}}
            <div class="mb-4">
                <div class="section-title">Description</div>

                <div class="info-box">
                    {{ $post->description ?? '-' }}
                </div>
            </div>

            {{-- MEDIA --}}
            <div>
                <div class="section-title">Media</div>

                @if($post->media->count())
                <div class="media-grid">
                    @foreach($post->media as $media)
                    @php
                    $url = Helper::showImage($media->file_path, true);
                    @endphp

                    @if(Str::startsWith($media->file_type, 'image/'))
                    <img src="{{ $url }}">
                    @elseif(Str::startsWith($media->file_type, 'video/'))
                    <video controls>
                        <source src="{{ $url }}">
                    </video>
                    @endif
                    @endforeach
                </div>
                @else
                <div class="info-box">No media available</div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection