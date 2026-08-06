---
name: code-simplification
description: Code simplification and refactoring skill for WareHouse-Koperasi
---

# Code Simplification Skill

After achieving a green test state, perform targeted simplification on modified code:

## Rules
1. **Scope Limit**: Inspect ONLY the lines and files modified in the current issue.
2. **Remove Redundancy**: Eliminate unnecessary helper functions, dead branches, and duplicate logic.
3. **Simplify Conditionals**: Convert deeply nested `if/else` statements into early guard returns.
4. **Preserve Security & Tenant Scope**: Never remove or simplify `warehouse_id` checks, Policy/Gate checks, or audit trail logging.
5. **Avoid Over-Engineering**: Prefer simple, clear Laravel idioms over complex custom abstractions.
6. **Re-test**: Always rerun `./automation/warehouse-orchestrator/agent-tools/agent-test-focused --filter <TestName>` after simplification.
