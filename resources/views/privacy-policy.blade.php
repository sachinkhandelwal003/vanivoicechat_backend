<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Page')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">

                        <h1 class="mb-3">Privacy Policy</h1>

                        <p class="text-muted mb-4">
                            <strong>Last Updated:</strong> {{ $data['last_updated'] }}
                        </p>

                        <p>
                            Welcome to <strong>{{ $data['app_name'] }}</strong> (“we”, “our”, “us”).
                            Your privacy is important to us. This Privacy Policy explains how we collect,
                            use, store, share, and protect your information when you use our mobile
                            application, website, and related services.
                        </p>

                        <hr>

                        <h3>1. Information We Collect</h3>
                        <p>We may collect the following categories of information:</p>

                        <h5 class="mt-3">1.1 Personal Information</h5>
                        <ul>
                            <li>Name</li>
                            <li>Username / User ID</li>
                            <li>Email address</li>
                            <li>Phone number (if applicable)</li>
                            <li>Profile image / avatar</li>
                            <li>Date of birth, gender, country, or other profile details (if provided)</li>
                        </ul>

                        <h5 class="mt-3">1.2 Account and App Usage Information</h5>
                        <ul>
                            <li>Login activity</li>
                            <li>Rooms joined or created</li>
                            <li>Chat messages and interactions</li>
                            <li>Followers, friends, invitations, and social activity</li>
                            <li>In-app preferences, settings, and user behavior</li>
                        </ul>

                        <h5 class="mt-3">1.3 Microphone / Audio Access</h5>
                        <p>
                            We may request access to your device microphone to enable voice chat,
                            live room communication, audio interaction, and similar features.
                            Microphone access is only used when required for active audio features.
                        </p>

                        <h5 class="mt-3">1.4 Camera / Image / Video Access</h5>
                        <p>
                            We may request access to your camera, gallery, photos, media, and storage
                            so that you can:
                        </p>
                        <ul>
                            <li>Upload profile images</li>
                            <li>Share photos and videos</li>
                            <li>Upload room covers, posts, or other media content</li>
                            <li>Capture photos/videos directly from the app</li>
                        </ul>

                        <h5 class="mt-3">1.5 Location Information</h5>
                        <p>
                            We may collect approximate or precise location data, subject to your device
                            permissions, to provide region-based services, location-relevant content,
                            room discovery, safety features, analytics, and improved user experience.
                        </p>

                        <h5 class="mt-3">1.6 Payment Information</h5>
                        <p>
                            If you purchase coins, subscriptions, gifts, premium features, or any paid
                            service within the app, payment processing may be handled through third-party
                            payment gateways such as Razorpay, Google Play Billing, Apple App Store Billing,
                            or other secure providers.
                        </p>
                        <ul>
                            <li>We do not store your full debit/credit card details.</li>
                            <li>We do not store CVV, PIN, OTP, or sensitive banking credentials.</li>
                            <li>Transaction-related information such as order IDs, payment status, and billing references may be stored for records and support.</li>
                        </ul>

                        <h5 class="mt-3">1.7 Device and Technical Information</h5>
                        <ul>
                            <li>IP address</li>
                            <li>Device model</li>
                            <li>Operating system and version</li>
                            <li>App version</li>
                            <li>Crash logs and diagnostics</li>
                            <li>Network information and device identifiers</li>
                        </ul>

                        <hr>

                        <h3>2. How We Use Your Information</h3>
                        <p>We may use your information for the following purposes:</p>
                        <ul>
                            <li>To create and manage your account</li>
                            <li>To provide voice chat rooms, social interaction, and communication services</li>
                            <li>To enable audio, image, and video sharing features</li>
                            <li>To process purchases, subscriptions, gifts, and payments</li>
                            <li>To personalize your experience and recommendations</li>
                            <li>To improve app performance, features, and security</li>
                            <li>To send notifications, updates, and alerts</li>
                            <li>To detect fraud, abuse, unauthorized access, or illegal activities</li>
                            <li>To comply with legal obligations and resolve disputes</li>
                        </ul>

                        <hr>

                        <h3>3. Permissions We May Request</h3>
                        <p>Depending on the features you use, the app may request the following permissions:</p>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Permission</th>
                                        <th>Purpose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Microphone</td>
                                        <td>Voice chat, live audio rooms, calling, speaking in rooms</td>
                                    </tr>
                                    <tr>
                                        <td>Camera</td>
                                        <td>Capture profile photo, image/video upload, in-app media features</td>
                                    </tr>
                                    <tr>
                                        <td>Photos / Storage / Media</td>
                                        <td>Select and upload images, videos, documents, or profile media</td>
                                    </tr>
                                    <tr>
                                        <td>Location</td>
                                        <td>Location-based suggestions, regional content, analytics, and relevant features</td>
                                    </tr>
                                    <tr>
                                        <td>Notifications</td>
                                        <td>Messages, room invites, gifts, updates, alerts, and app activity</td>
                                    </tr>
                                    <tr>
                                        <td>Internet / Network</td>
                                        <td>Core app functionality, real-time communication, data sync</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <h3>4. Sharing of Information</h3>
                        <p>
                            We do not sell your personal information to third parties. However, we may share
                            information in the following cases:
                        </p>
                        <ul>
                            <li>With payment gateways to process your transactions</li>
                            <li>With hosting, cloud storage, analytics, and notification service providers</li>
                            <li>With audio/video communication providers for real-time features</li>
                            <li>With legal authorities when required by law, regulation, or court order</li>
                            <li>With business partners or service providers working on our behalf under confidentiality obligations</li>
                        </ul>

                        <hr>

                        <h3>5. Third-Party Services</h3>
                        <p>
                            Our app may integrate with third-party services such as:
                        </p>
                        <ul>
                            <li>Payment gateways (e.g. Razorpay, Google Play, Apple Billing)</li>
                            <li>Cloud messaging / push notifications (e.g. Firebase)</li>
                            <li>Audio / live communication platforms (e.g. Agora or similar providers)</li>
                            <li>Cloud hosting, storage, analytics, and crash reporting tools</li>
                        </ul>

                        <p>
                            These third-party services may collect, process, and store data according to
                            their own privacy policies. We encourage users to review those policies separately.
                        </p>

                        <hr>

                        <h3>6. Data Storage and Security</h3>
                        <p>
                            We implement reasonable technical and organizational measures to protect your
                            information, including secure servers, access control, encrypted communication,
                            authenticated APIs, and monitoring systems. However, no electronic transmission
                            or storage method can be guaranteed to be 100% secure.
                        </p>

                        <hr>

                        <h3>7. Data Retention</h3>
                        <p>
                            We retain your information for as long as your account remains active, or as
                            necessary to provide services, resolve disputes, enforce agreements, maintain
                            records, and comply with legal obligations.
                        </p>

                        <p>
                            Some data may remain in backups, logs, or transaction records for a limited period
                            even after deletion requests, where legally permitted or operationally necessary.
                        </p>

                        <hr>

                        <h3>8. Account Deletion</h3>
                        <p>
                            You may request deletion of your account at any time through the app settings
                            or by contacting us.
                        </p>

                        <p><strong>When your account is deleted:</strong></p>
                        <ul>
                            <li>Your profile information may be permanently removed or anonymized</li>
                            <li>Your uploaded content may be deleted, except where retention is required by law</li>
                            <li>Your payment, billing, fraud-prevention, and legal compliance records may be retained as necessary</li>
                            <li>Some shared content, chat history, or room activity visible to other users may remain in limited form</li>
                        </ul>

                        <p>
                            To request account deletion, go to the app’s me/settings section or email us at
                            <a href="mailto:{{ $data['support_email'] }}">{{ $data['support_email'] }}</a>.
                        </p>

                        <hr>

                        <h3>9. User Content</h3>
                        <p>
                            Any text, audio, image, video, profile data, messages, room content, gifts,
                            or other content you upload or share through the app may be processed and stored
                            for service delivery, moderation, safety, reporting, and abuse-prevention purposes.
                        </p>

                        <hr>

                        <h3>10. Children’s Privacy & Safety</h3>

                        <p>
                            Our platform is designed for users who meet the minimum age requirement as per applicable laws.
                            We do not knowingly allow children below the required age (typically under 13 or as per regional law)
                            to create accounts or use our services without proper parental or guardian consent.
                        </p>

                        <p><strong>Child Safety Measures:</strong></p>
                        <ul>
                            <li>We strictly prohibit any form of child abuse, exploitation, or harmful behavior on our platform</li>
                            <li>We actively monitor content, chats, and user activity using automated systems and moderation tools</li>
                            <li>Users can report any inappropriate content, behavior, or users directly through the app</li>
                            <li>We take immediate action against accounts involved in harmful or suspicious activities</li>
                        </ul>

                        <p><strong>Reporting & Enforcement:</strong></p>
                        <ul>
                            <li>Users can report abuse via in-app reporting tools or by contacting us at
                                <a href="mailto:{{ $data['support_email'] }}">{{ $data['support_email'] }}</a>
                            </li>
                            <li>Reported content is reviewed by our moderation team</li>
                            <li>We may suspend or permanently ban accounts violating child safety policies</li>
                            <li>In serious cases, we may report incidents to legal authorities as required by law</li>
                        </ul>

                        <p><strong>Data Handling for Children:</strong></p>
                        <ul>
                            <li>If we become aware that a child has provided personal data without proper consent, we will take steps to delete such information</li>
                            <li>Parents or guardians can contact us to request removal of their child’s data</li>
                        </ul>

                        <p>
                            We are committed to maintaining a safe environment for all users and strictly enforce our policies
                            to protect minors from harm.
                        </p>

                        <hr>

                        <h3>11. Your Rights</h3>
                        <p>Depending on your jurisdiction, you may have the right to:</p>
                        <ul>
                            <li>Access your personal data</li>
                            <li>Correct or update your information</li>
                            <li>Request deletion of your account or data</li>
                            <li>Withdraw consent for optional permissions</li>
                            <li>Object to certain processing activities</li>
                            <li>Request information about how your data is used</li>
                        </ul>

                        <hr>

                        <h3>12. Changes to This Privacy Policy</h3>
                        <p>
                            We may update this Privacy Policy from time to time. Any changes will be posted
                            on this page with a revised “Last Updated” date. Continued use of the app after
                            such changes means you accept the updated policy.
                        </p>

                        <hr>

                        <h3>13. Contact Us</h3>
                        <p>
                            If you have any questions, concerns, or requests related to this Privacy Policy,
                            you may contact us at:
                        </p>

                        <ul>
                            <li><strong>Company:</strong> {{ $data['company_name'] }}</li>
                            <li><strong>Email:</strong> <a href="mailto:{{ $data['support_email'] }}">{{ $data['support_email'] }}</a></li>
                            <li><strong>Website:</strong> <a href="{{ $data['website_url'] }}">{{ $data['website_url'] }}</a></li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>