import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, isSupported } from 'firebase/messaging';

/**
 * Public Firebase web config only — never server credentials. Consent is
 * always driven by explicit user interaction (a button click calling
 * enablePushNotifications()), never requested automatically on page load.
 */
const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

let messagingInstance = null;

async function resolveMessaging() {
    if (messagingInstance) {
        return messagingInstance;
    }

    if (typeof Notification === 'undefined' || !(await isSupported())) {
        return null;
    }

    messagingInstance = getMessaging(initializeApp(firebaseConfig));

    return messagingInstance;
}

export function getPushPermissionStatus() {
    if (typeof Notification === 'undefined') {
        return 'unsupported';
    }

    return Notification.permission;
}

/**
 * Only ever called from an explicit user action (a button click in the
 * preferences UI) — this is what makes requesting browser permission here
 * safe rather than an unsolicited page-load prompt.
 */
export async function enablePushNotifications() {
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return { status: permission };
    }

    const messaging = await resolveMessaging();

    if (!messaging) {
        throw new Error('Push messaging is not supported in this browser.');
    }

    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

    const fcmToken = await getToken(messaging, {
        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
        serviceWorkerRegistration: registration,
    });

    if (!fcmToken) {
        throw new Error('Failed to obtain a push registration token.');
    }

    await registerDeviceToken(fcmToken);

    return { status: 'granted' };
}

window.WarehousePush = { getPushPermissionStatus, enablePushNotifications };
window.dispatchEvent(new CustomEvent('push:ready'));

async function registerDeviceToken(fcmToken) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const response = await fetch('/notifications/devices', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken ?? '',
        },
        body: JSON.stringify({
            token: fcmToken,
            provider: 'fcm',
            platform: 'web',
            device_name: navigator.platform || 'Browser',
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to register this device for push notifications.');
    }

    return response.json();
}
