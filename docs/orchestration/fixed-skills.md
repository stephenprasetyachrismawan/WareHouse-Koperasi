# Fixed Skills Policy

The primary agent must use a deliberately small skill set.

## Mandatory Skills
1. `laravel-boost`: Framework conventions, Pint, Pest, policies, and tenant scoping.
2. `test-driven-development`: Strict RED -> GREEN -> REFACTOR development cycle.
3. `code-simplification`: Post-implementation code cleanup without changing behavior.

## Additional Skills Allowlist
Configured in `automation/warehouse-orchestrator/config/allowed-additional-skills.json`:
- `fortify-development`
- `livewire-development`
- `passkeys-development`
- `pest-testing`
- `tenant-security-audit`

Maximum 2 additional skills per issue.
