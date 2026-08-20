# Reverb Runbook

Reverb is an auxiliary realtime process. Inbox persistence and HTTP dashboard reads remain the source of truth when it is unavailable.

Check the supervised process, TLS/reverse proxy, allowed origins, Redis dependency, and `/health/live`. Restart through the process manager, not an ad-hoc long-lived SSH command. After recovery, verify browser reconnect and Inbox refresh; missed events are recovered from persistent Inbox state.
