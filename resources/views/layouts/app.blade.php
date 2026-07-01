<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('partial.common.header')
<style>
    #userProfileSidebar {
        position: fixed;
        top: 0;
        right: -550px;
        width: 550px;
        height: 100vh;
        overflow-y: auto;
        background: #fff;
        z-index: 99999;
        transition: .3s;
        box-shadow: -8px 0 30px rgba(0, 0, 0, .15);
    }

    #userProfileSidebar.show {
        right: 0;
    }




    .support-user {
        cursor: pointer;
        transition: 0.2s;
    }

    .support-user:hover {
        background: #e9f2ff;
    }

    .support-user.active {
        background: #dbe9ff;
    }

    .support-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #0d6efd;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .msg {
        padding: 10px 14px;
        border-radius: 18px;
        max-width: 70%;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .msg-admin {
        background: #0d6efd;
        color: #fff;
        align-self: flex-end;
    }

    .msg-user {
        background: #e4e6eb;
        align-self: flex-start;
    }



    #imagePreviewModal .modal-dialog {
        max-width: 100vw;
        height: 100vh;
        margin: 0;
    }

    #imagePreviewModal .modal-content {
        background: rgba(0, 0, 0, .75);
        border: 0;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #globalPreviewImage {
        max-width: 300px;
        max-height: 300px;
        object-fit: contain;
    }

    #imagePreviewModal .btn-close {
        position: absolute;
        top: 20px;
        right: 20px;
        filter: invert(1);
    }
</style>

<body class="layout-boxed">
    <!--  BEGIN NAVBAR  -->
    <div class="header-container">
        <header class="header navbar navbar-expand-sm expand-header">
            <ul class="navbar-item theme-brand flex-row text-center">
                <li class="nav-item theme-logo">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('storage/' . $site_settings['favicon']) }}" class="navbar-logo" alt="logo" />
                    </a>
                </li>
                <li class="nav-item theme-text">
                    <a href="{{ route('dashboard') }}" class="nav-link"> {{ $site_settings['application_name'] }} </a>
                </li>
            </ul>

            <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area">
                <li class="nav-item dropdown user-profile-dropdown order-lg-0 order-1">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar-container">
                            <div class="avatar avatar-sm avatar-indicators avatar-online">
                                <img alt="" src="{{ asset('storage/' . Auth::user()->image) }}"
                                    class="rounded-circle profile-img" />
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu position-absolute" aria-labelledby="userProfileDropdown">
                        <div class="user-profile-section">
                            <div class="media mx-auto">
                                <div class="me-2"></div>
                                <div class="media-body">
                                    @if (Auth::check())
                                    <span class="dropdown-item fw-bold text-warning">
                                        <h5>{{ Auth::user()->name }}</h5>
                                        <p>Admin</p>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('profile') }}">
                                <i class="fa-duotone fa-user me-1"></i>
                                <span>Profile</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('lock') }}">
                                <i class="fa-duotone fa-lock"></i>
                                <span>Lock Screen</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa-regular fa-arrow-right-from-bracket me-1"></i>
                                <span>Log Out</span>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </header>
    </div>
    <!--  END NAVBAR  -->

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <!--  BEGIN SIDEBAR  -->
        <div class="sidebar-wrapper sidebar-theme">
            @include('partial.sidebar')
        </div>
        <!--  END SIDEBAR  -->

        <div id="content" class="main-content">
            <!-- ===============================================-->
            <!--    Main Content-->
            <!-- ===============================================-->
            <div class="layout-px-spacing">
                <div class="middle-content container-xxl p-0">
                    <div class="secondary-nav">
                        @include('partial.common.breadcrumb')
                    </div>
                    <div class="layout-top-spacing">
                        <div class="container-xxl p-0">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===============================================-->
            <!--    End of Main Content-->
            <!-- ===============================================-->

            <!-- ===============================================-->
            <!--    FOOTER      -->
            <!-- ===============================================-->
            <div class="footer-wrapper">
                <div class="footer-section f-section-1">
                    {{ $site_settings['copyright'] }}
                </div>
                <div class="footer-section f-section-2">
                    <p class="">
                        Delvelop By : <a href="https://kotiboxglobaltech.com/" target="_blank">Kotibox Global Tech</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- User Profile Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="userProfileCanvas" aria-labelledby="userProfileCanvasLabel" style="width:500px;">

        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">User Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-0" id="userProfileContent">

            <div class="text-center py-5">

                <div class="spinner-border text-primary"></div>

                <div class="mt-2">
                    Loading...
                </div>

            </div>

        </div>

    </div>

    @include('partial.common.footer')

    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">

                <button type="button"
                    class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                    data-bs-dismiss="modal">
                </button>

                <div class="text-center">
                    <img id="globalPreviewImage"
                        src=""
                        onclick="event.stopPropagation();"
                        style="max-width:400px;max-height:400px;">
                </div>

            </div>
        </div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.0/echo.iife.js"></script>

    <script>
        $(document).on('click', '.user-profile-trigger',
            function () {

                let userId = $(this).data('user-id');

                let canvas = new bootstrap.Offcanvas(
                    document.getElementById('userProfileCanvas')
                );

                canvas.show();

                $('#userProfileContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <div class="mt-2">Loading...</div>
                    </div>
                `);

                $.ajax({

                    url: '/user-profile/' + userId,
                    type: 'GET',

                    success: function(response) {

                        $('#userProfileContent')
                            .html(response.html);

                    },

                    error: function() {

                        $('#userProfileContent').html(`
                            <div class="alert alert-danger m-3">
                                User details not found.
                            </div>
                        `);

                    }

                });

            }
        );

    </script>

    <script>
        // Pusher.logToConsole = true;
        Pusher.logToConsole = false;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: "{{ env('PUSHER_APP_KEY') }}",
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            forceTLS: true
        });
    </script>


    <script>
        firebase.initializeApp({
            apiKey: "AIzaSyC9jroFNvMvkJi3r8ksCC-TqL6guZzpY_Y",
            authDomain: "vani-voice-chat-app-6f78c.firebaseapp.com",
            projectId: "vani-voice-chat-app-6f78c",
            storageBucket: "vani-voice-chat-app-6f78c.firebasestorage.app",
            messagingSenderId: "693048345824",
            appId: "1:693048345824:web:e67f8a50dc519da6a17c85",
            measurementId: "G-VQN9BE83VZ"
        });
        const messaging = firebase.messaging();

        Notification.requestPermission().then(function(permission) {

            if (permission === 'granted') {
                // alert('6546');
                messaging.getToken({
                    vapidKey: "BJE1je_P1yfwOLg5un0rUP3OLlPW5FHDSAYOoFgjQYOFPZqXZIVLZfcCZNacwL-lGwo6m2dsnwxSbCMkQTEszeQ"
                }).then(function(token) {

                    // console.log(token);


                    fetch("{{ route('save.token') }}", {

                        method: "POST",
                        credentials: "same-origin",
                        headers: {

                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content

                        },

                        body: JSON.stringify({

                            token: token

                        })

                    });

                });

            }

        });
        messaging.onMessage(function(payload) {

            console.log("Notification received:", payload);

            new Notification(payload.notification.title, {

                body: payload.notification.body,

                icon: '/firebase-logo.png'

            });

        });
    </script>

    <script>
        $(document).on('click', '.image-preview', function() {

            let imageUrl = $(this).data('image');

            $('#globalPreviewImage').attr('src', imageUrl);

            let modal = new bootstrap.Modal(
                document.getElementById('imagePreviewModal')
            );

            modal.show();
        });

        $(document).on('click', '#imagePreviewModal', function(e) {

            if (!$(e.target).closest('#globalPreviewImage').length) {

                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('imagePreviewModal')
                );

                if (modal) {
                    modal.hide();
                }
            }
        });
    </script>

    @stack('scripts')
</body>

</html>