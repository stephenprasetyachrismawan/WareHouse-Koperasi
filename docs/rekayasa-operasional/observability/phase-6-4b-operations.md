# Phase 6.4B Observability

## Health

- `GET /health/live` is public and returns only `{"status":"ok"}`.
- `GET /health/ready` checks the database and returns `{"status":"ready"}` or a safe 503 `{"status":"unavailable"}`.
- Neither endpoint exposes hosts, versions, SQL, exceptions, tenant records, or secrets.

## Structured request context

The web middleware records `http.request` with request ID, actor ID when authenticated, active warehouse ID when available, route/action, outcome, status, and latency. It never records the request body, credentials, signed URLs, evidence contents, OAuth tokens, or FCM tokens. The request ID is correlation metadata only and is not authorization.

## Signals and ownership

| Signal | Severity | First diagnostic | Owner |
| --- | --- | --- | --- |
| readiness 503 / DB unavailable | critical | `GET /health/ready`, DB connectivity and logs | database/platform |
| stock reconciliation mismatch | critical | `php artisan stock:reconcile --warehouse=<id>` | inventory/operations |
| failed critical queue job | high | `php artisan queue:failed`, inspect then retry | operations |
| push delivery retry/permanent failure | medium | notification delivery rows and failed jobs | notifications |
| export failure | medium | report export status and job failure | operations |
| Reverb outage | medium | process health plus `/inbox` HTTP path | platform |

The application emits signals; deployment infrastructure must connect them to alert delivery and on-call ownership before production sign-off.
