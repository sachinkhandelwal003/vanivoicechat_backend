@php
$lastMessage = $conversation->messages->first();
@endphp

<div class="support-user d-flex align-items-center p-3"
     onclick="selectUser(this, {{ $conversation->id }})">

    <div class="support-avatar me-3">
        @if($conversation->user && $conversation->user->image)
            <img src="{{ Helper::showImage($conversation->user->image) }}"
                 style="width:45px;height:45px;border-radius:50%;">
        @else
            {{ strtoupper(substr($conversation->user->name ?? 'U', 0, 2)) }}
        @endif
    </div>

    <div class="flex-grow-1">
        <div class="fw-semibold">
            {{ $conversation->user->name ?? '' }}
        </div>

        <small class="text-muted">
            {{ $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->message, 40) : 'No messages yet' }}
        </small>
    </div>

    @if($conversation->unread_count > 0)
        <span class="badge bg-danger rounded-pill">
            {{ $conversation->unread_count }}
        </span>
    @endif

</div>