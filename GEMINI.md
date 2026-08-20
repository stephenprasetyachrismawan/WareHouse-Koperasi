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

### Core Non-Negotiable Engineering Rules
- Every tenant record is scoped by `warehouse_id`.
- Access control is strictly enforced via Laravel Policies and Gates (never UI visibility alone).
- Database mutations follow strict TDD (Red-Green-Refactor).
- Stock movements and approval trails are append-only.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
