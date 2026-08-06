# Orchestration Troubleshooting Guide

## Common Scenarios

### 1. Issue stuck on `agent:blocked`
- Reason: User authorization missing or model unavailable.
- Fix: Run `npm run auth:user -- <username>` or `npm run config:set-model -- --model <model-slug>`.

### 2. Issue stuck on `agent:failed`
- Reason: TDD, Pint, PHPStan, or test gate failure.
- Fix: Review job details with `npm run jobs:show -- <issue-number>`, apply fixes, then add `agent:retry` label.

### 3. Composer dev health check failure
- Reason: Syntax error or port conflict in background dev server.
- Fix: Check logs via `npm run dev-session:logs` and restart via `npm run dev-session:restart`.
