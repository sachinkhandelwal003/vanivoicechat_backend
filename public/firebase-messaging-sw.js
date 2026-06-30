importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js",
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js",
);

firebase.initializeApp({
  apiKey: "AIzaSyC9jroFNvMvkJi3r8ksCC-TqL6guZzpY_Y",
  authDomain: "vani-voice-chat-app-6f78c.firebaseapp.com",
  projectId: "vani-voice-chat-app-6f78c",
  storageBucket: "vani-voice-chat-app-6f78c.firebasestorage.app",
  messagingSenderId: "693048345824",
  appId: "1:693048345824:web:e67f8a50dc519da6a17c85",
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
  self.registration.showNotification(
    payload.notification.title,

    {
      body: payload.notification.body,

      icon: "/logo.png",
    },
  );
});
