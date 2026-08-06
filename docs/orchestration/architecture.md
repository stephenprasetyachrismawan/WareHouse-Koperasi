# Orchestration Architecture

This document describes the high-level architecture of the autonomous GitHub Issue Orchestrator for `stephenprasetyachrismawan/WareHouse-Koperasi`.

## Overview
The architecture strictly separates deterministic orchestration tasks (managed by TypeScript Node.js, tmux, and SQLite) from AI coding tasks (managed by Google Antigravity CLI `agy`).

```text
GitHub Issue (agent:run)
       ↓
Tmux Session (warehouse-issue-detector)
       ↓
Identity Resolution & Worktree Setup (/srv/warehouse-koperasi/worktrees/issue-X)
       ↓
Antigravity CLI (warehouse-laravel agent + flash model)
       ↓
Deterministic Local Quality Gate (agent-final-test)
       ↓
Composer Dev Supervisor Verification (warehouse-composer-dev)
       ↓
Git Commit & Push (Effective Contributor Identity)
       ↓
GitHub Pull Request & Squash Merge
       ↓
Post-Merge Main Sync & Health Check (agent:done)
```

## Key Architectural Boundaries
1. **No GitHub Actions Gate**: All quality checks (Pint, PHPStan, PHPUnit/Pest, SQLite DB integration) run locally on the VPS.
2. **Worktree Isolation**: Each issue runs inside an isolated Git worktree under `/srv/warehouse-koperasi/worktrees/`.
3. **Identity Scoping**: Commits and PRs are created using the effective contributor's GitHub identity and Git author/committer configs.
4. **Persistent Process Supervision**: `composer run dev` runs inside a persistent tmux supervisor session (`warehouse-composer-dev`) with target switching and log scanning.
