# Disaster Recovery & System Reboot

## VPS Reboot Recovery
After a VPS reboot, the orchestrator sessions can be restored with:

```bash
npm run tmux:start
```

## Cleaning Up Stale Worktrees
If a job was interrupted by host crash:
```bash
git worktree prune
rm -rf /srv/warehouse-koperasi/worktrees/issue-*
```
