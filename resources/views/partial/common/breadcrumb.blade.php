@php
$name = request()->route()->getName();
$parts = array_filter(explode('.', $name));
@endphp

<div class="breadcrumbs-container" data-page-heading="Analytics" style="background-color: #5d3eb1cf;">
    <header class="header navbar navbar-expand-sm">

        <!-- Sidebar Toggle -->
        <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
            <i class="fa-duotone fa-bars fs-5"></i>
        </a>

        <!-- Breadcrumb + Right Area -->
        <div class="d-flex justify-content-between align-items-center w-100 breadcrumb-content">

            <!-- LEFT SIDE (Breadcrumb) -->
            <div class="page-header">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>

                        @foreach($parts as $part)
                        <li class="breadcrumb-item">
                            {{ ucwords(str_ireplace(['-', '_'], ' ', $part)) }}
                        </li>
                        @endforeach
                    </ol>
                </nav>
            </div>

            <!-- RIGHT SIDE (Quick Support Button) -->
            <!-- <div class="me-3">
                <a href="{{ route('support.index') }}"
                    class="quick-support-btn position-relative d-flex align-items-center rounded-pill gap-2">

                    <i class="iconoir-headset-help"></i>
                    <span>Customer Support</span>

                    <span id="supportBadge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size:10px; padding:5px 7px; {{ ($totalUnread ?? 0) > 0 ? '' : 'display:none;' }}">
                        {{ $totalUnread ?? 0 }}
                    </span>
                </a>
            </div> -->

        </div>

    </header>
    <style>
        .quick-support-btn {
            display: flex !important;
            align-items: center;
            gap: 8px;

            padding: 6px 14px !important;
            border-radius: 999px;

            background: linear-gradient(135deg, #6366f1, #06b6d4);
            color: #fff !important;
            text-decoration: none;

            box-shadow: 0 6px 18px rgba(79, 70, 229, .35);
            transition: .25s ease;
        }

        .quick-support-btn i {
            font-size: 18px;
        }

        .qs-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .qs-support {
            font-size: 12px;
            font-weight: 700;
        }

        .quick-support-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(79, 70, 229, .5);
        }
    </style>
    <style>
        /* ===== SUPPORT PANEL ===== */

        .support-wrapper {
            height: 100%;
            overflow: hidden;
        }

        /* USERS */
        .support-users {
            width: 160px;
            background: #f8f9fa;
            overflow-y: auto;
        }

        .support-user {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: .2s;
        }

        .support-user:hover {
            background: #e9f2ff;
        }

        .support-user.active {
            background: #dbe9ff;
        }

        /* avatar */
        .support-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #0d6efd;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 8px;
            font-size: 14px;
        }

        /* CHAT */
        .support-chat {
            background: #ffffff;
        }

        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f1f3f5;
        }

        /* bubbles */
        .msg {
            margin-bottom: 12px;
            max-width: 80%;
            padding: 9px 13px;
            border-radius: 14px;
            font-size: 14px;
            position: relative;
        }

        .msg-user {
            background: #e4e6eb;
            align-self: flex-start;
        }

        .msg-admin {
            background: #0d6efd;
            color: #fff;
            align-self: flex-end;
        }

        .msg-time {
            font-size: 10px;
            opacity: .7;
            margin-top: 3px;
        }

        /* INPUT */
        .chat-input-box {
            border-top: 1px solid #e5e5e5;
            padding: 10px;
            background: #fff;
        }

        .chat-input-box input {
            height: 42px;
            border-radius: 20px !important;
            padding-left: 15px;
        }

        .chat-input-box button {
            border-radius: 20px;
            padding: 0 18px;
        }
    </style>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        let totalUnread = {{ $totalUnread ?? 0 }};

        const badge = document.getElementById('supportBadge');
        const supportBtn = document.querySelector('.quick-support-btn');

        function updateGlobalBadge() {

            if (!badge) return;

            if (totalUnread > 0) {
                badge.style.display = 'inline-block';
                badge.innerText = totalUnread;
            } else {
                badge.style.display = 'none';
            }
        }

        // 🔥 Reset when support page opened
        if (supportBtn) {
            supportBtn.addEventListener('click', function() {
                totalUnread = 0;
                updateGlobalBadge();
            });
        }

        // 🔥 REALTIME LISTENER
        if (typeof Echo !== 'undefined') {

            Echo.channel('support-global')
                .listen('.support.message', (e) => {

                    console.log("Realtime global:", e);

                    if (e.message.sender_type === 'user') {

                        totalUnread++;
                        updateGlobalBadge();
                    }

                });
        }

    });
</script>