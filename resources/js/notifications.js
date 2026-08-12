import './echo';

/**
 * Realtime delivery convenience for the persistent Inbox (Phase 6.1). The
 * database remains authoritative: this module never patches notification
 * content into the DOM directly — it only asks already-mounted Livewire
 * components to re-fetch (`Livewire.dispatch`), so the badge/list reflect
 * exactly what a normal page load would show. A missed or duplicate event
 * only ever costs a redundant re-fetch, never an incorrect one.
 */

const userId = document.querySelector('meta[name="auth-user-id"]')?.content;
const activeWarehouseId = document.querySelector('meta[name="active-warehouse-id"]')?.content || null;

if (userId) {
    const seenNotificationUuids = new Set();

    const rememberSeen = (uuid) => {
        seenNotificationUuids.add(uuid);
        if (seenNotificationUuids.size > 200) {
            seenNotificationUuids.delete(seenNotificationUuids.values().next().value);
        }
    };

    const handleNotificationCreated = (payload) => {
        if (!payload?.uuid || seenNotificationUuids.has(payload.uuid)) {
            return;
        }
        rememberSeen(payload.uuid);

        // A recipient can belong to multiple warehouses; only react when the
        // notification concerns the warehouse currently active in this tab.
        if (payload.warehouse_id !== null && String(payload.warehouse_id) !== String(activeWarehouseId)) {
            return;
        }

        window.Livewire?.dispatch('inbox-notification-received', { uuid: payload.uuid });

        window.Flux?.toast({
            heading: payload.title,
            text: payload.message,
        });
    };

    // Subscribe immediately — window.Echo is already set by the `./echo`
    // import above. Waiting for 'livewire:init' here would be too late:
    // Livewire's own (non-module) script runs and fires that event before
    // this deferred module executes, so the listener would never attach.
    const subscribe = (channel) => {
        window.Echo.private(channel)
            .listen('.notification.created', handleNotificationCreated);
    };

    if (activeWarehouseId) {
        subscribe(`user.${userId}.warehouse.${activeWarehouseId}.notifications`);
    }

    subscribe(`user.${userId}.platform.notifications`);
}
