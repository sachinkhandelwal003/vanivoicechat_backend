<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Delete Account')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <h1 class="mb-3 text-danger">Delete Account Instructions</h1>

                    <p class="text-muted mb-4">
                        <strong>Last Updated:</strong> {{ $data['last_updated'] }}
                    </p>

                    <p>
                        This page explains how users of <strong>{{ $data['app_name'] }}</strong>
                        can request the deletion of their account and associated data.
                    </p>

                    <hr>

                    <h3>1. How to Delete Your Account</h3>

                    <p>You can delete your account using the following methods:</p>

                    <h5 class="mt-3">Option 1: From Mobile App</h5>
                    <ul>
                        <li>Open the app</li>
                        <li>Go to <strong>Me / Profile</strong></li>
                        <li>Go to <strong>Settingt</strong></li>
                        <li>Tap on <strong>Delete Account Data</strong></li>
                        <li>Confirm your request</li>
                    </ul>

                    <h5 class="mt-3">Option 2: Request via Email</h5>
                    <p>
                        If you are unable to access your account, you can send a request to:
                    </p>

                    <p>
                        📧 <a href="mailto:{{ $data['support_email'] }}">{{ $data['support_email'] }}</a>
                    </p>

                    <p>
                        Please include your registered email or user ID for verification.
                    </p>

                    <hr>

                    <h3>2. What Happens After Deletion</h3>

                    <ul>
                        <li>Your account will be permanently deleted</li>
                        <li>Your profile information will be removed</li>
                        <li>Your uploaded images, videos, and content may be deleted</li>
                        <li>You will lose access to coins, purchases, and subscriptions</li>
                        <li>You will not be able to recover your account</li>
                    </ul>

                    <hr>

                    <h3>3. Data That May Be Retained</h3>

                    <p>Some data may be retained for legal and operational purposes:</p>

                    <ul>
                        <li>Payment transaction records (for legal compliance)</li>
                        <li>Fraud prevention and security logs</li>
                        <li>Data required by law or government authorities</li>
                    </ul>

                    <hr>

                    <h3>4. Processing Time</h3>

                    <p>
                        Account deletion requests are typically processed within
                        <strong>3 to 7 working days</strong>.
                    </p>

                    <hr>

                    <h3>5. Important Notes</h3>

                    <ul>
                        <li>Deletion is permanent and cannot be undone</li>
                        <li>Make sure to use your coins or subscriptions before deletion</li>
                        <li>Uninstalling the app does NOT delete your account</li>
                    </ul>

                    <hr>

                    <h3>6. Contact Us</h3>

                    <p>If you have any questions:</p>

                    <ul>
                        <li><strong>Company:</strong> {{ $data['company_name'] }}</li>
                        <li><strong>Email:</strong>
                            <a href="mailto:{{ $data['support_email'] }}">
                                {{ $data['support_email'] }}
                            </a>
                        </li>
                        <li><strong>Website:</strong>
                            <a href="{{ $data['website_url'] }}">
                                {{ $data['website_url'] }}
                            </a>
                        </li>
                    </ul>

                    <hr>

                    <div class="alert alert-warning mt-4">
                        ⚠️ Deleting your account is a permanent action and cannot be reversed.
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</body>

</html>