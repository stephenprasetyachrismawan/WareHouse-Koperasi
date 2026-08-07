# Command Execution Policy Rules

## Approved Commands
The agent is permitted to execute:
- Fixed command tools under `./automation/warehouse-orchestrator/agent-tools/*`
- Safe read-only Git status and inspection commands:
  - `git status`
  - `git diff`
  - `git log`
  - `git show`
  - `git branch --show-current`
  - `git rev-parse`

## Denied Commands
The agent is STRICTLY PROHIBITED from executing:
- System administration commands: `sudo`, `su`, `systemctl`, `service`, `mount`, `umount`, `iptables`, `nft`
- Container management: `docker`, `podman`, `kubectl`
- Destructive file/git commands: `rm -rf`, `git push`, `git force-push`, `git reset --hard`, `git clean -fd`
- GitHub publication/PR commands: `gh pr create`, `gh pr merge`, `gh issue edit`
- Direct long-running dev servers: `composer run dev`, `php artisan serve`, `npm run dev`
