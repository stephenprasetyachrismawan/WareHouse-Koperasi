# GEMINI.md

## Project Context and Instructions

This repository (`stephenprasetyachrismawan/WareHouse-Koperasi`) operates under strict domain, security, architectural, and orchestration guidelines.

### Mandatory Authoritative References
Before analyzing or modifying code, inspect:

1. `AGENTS.md` - Primary rules, non-negotiable guidelines, and workflow definitions.
2. `PRD.md` - Business requirements and functional scope.
3. `BATASAN.md` - Explicit boundaries and deliberate scope overrides.
4. `SECURITY-RULES.md` - Multi-tenant security, authorization policies, and safety rules.
5. `ARCHITECTURE.md` - Application design, module patterns, and database contracts.
6. `UI-RULES.md` - User interface expectations and Livewire/Blade guidelines.
7. `.agent/WORKFLOW.md` - Step-by-step TDD, security review, and intake workflow.
8. `.agent/ORCHESTRATION.md` - GitHub Issue autonomous pipeline and Antigravity execution rules.

### Core Non-Negotiable Engineering Rules
- Every tenant record is scoped by `warehouse_id`.
- Access control is strictly enforced via Laravel Policies and Gates (never UI visibility alone).
- Database mutations follow strict TDD (Red-Green-Refactor).
- Stock movements and approval trails are append-only.
- Direct execution of publishing, merging, or system administration is restricted to the deterministic orchestrator.
