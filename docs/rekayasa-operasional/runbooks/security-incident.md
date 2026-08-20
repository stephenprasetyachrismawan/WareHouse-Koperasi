# Security Incident Runbook

## Detection

Preserve request ID, actor, warehouse, route, audit identifier, job ID, and timestamps without copying secrets, tokens, signed URLs, evidence bodies, or full request payloads.

## Containment

Suspend affected accounts/memberships, revoke sessions and device tokens where appropriate, restrict support impersonation, and isolate affected storage or credentials. Do not delete audit history.

## Recovery and validation

Rotate exposed credentials, inspect tenant access and audit history, run focused tenant/privilege/file tests, reconcile stock if inventory access was involved, and validate private evidence authorization.

## Escalation

Security owner coordinates disclosure and communications; database/inventory/platform owners handle data, stock, and infrastructure impact. Record the post-incident review and accepted follow-up risks in secured operational documentation.
