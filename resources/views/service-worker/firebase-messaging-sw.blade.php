importScripts('https://www.gstatic.com/firebasejs/12.17.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.17.1/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: {{ \Illuminate\Support\Js::from(config('services.firebase_web.api_key')) }},
    authDomain: {{ \Illuminate\Support\Js::from(config('services.firebase_web.auth_domain')) }},
    projectId: {{ \Illuminate\Support\Js::from(config('services.firebase_web.project_id')) }},
    messagingSenderId: {{ \Illuminate\Support\Js::from(config('services.firebase_web.messaging_sender_id')) }},
    appId: {{ \Illuminate\Support\Js::from(config('services.firebase_web.app_id')) }},
});

const messaging = firebase.messaging();

// Background push only ever carries a generic title/body plus the
// notification UUID — never the full Inbox message, never a direct URL.
messaging.onBackgroundMessage((payload) => {
    const notificationUuid = payload?.data?.notification_uuid ?? null;
    const title = payload?.notification?.title || 'Notifikasi Baru';
    const body = payload?.notification?.body || '';

    self.registration.showNotification(title, {
        body,
        icon: '/favicon.svg',
        data: { notification_uuid: notificationUuid },
    });
});

// Never trust a URL carried by the push payload itself — clicking a
// notification only ever navigates to our own internal deep-link resolver
// by UUID, which re-authenticates and re-authorizes before redirecting
// anywhere. No eval, no rendering of push-supplied HTML.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const notificationUuid = event.notification.data?.notification_uuid;
    const targetUrl = notificationUuid ? `/notifications/${notificationUuid}` : '/inbox';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.focus();
                    return client.navigate(targetUrl);
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
