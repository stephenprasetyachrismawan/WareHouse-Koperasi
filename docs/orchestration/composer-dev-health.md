# Composer Dev Health Supervision

`composer run dev` is managed continuously by `automation/warehouse-orchestrator/scripts/dev-supervisor.sh` inside the `warehouse-composer-dev` tmux session.

## Operation
- Target worktree is set dynamically via `/srv/warehouse-koperasi/state/active-dev-worktree`.
- When an issue starts, target switches to `/srv/warehouse-koperasi/worktrees/issue-X`.
- When an issue completes, target returns to `/srv/warehouse-koperasi/control`.
- The supervisor enforces a 45s warm-up period and scans logs after startup marker for fatal patterns.

## Commands
```bash
npm run dev-session:status
npm run dev-session:health
npm run dev-session:logs
npm run dev-session:restart
```
