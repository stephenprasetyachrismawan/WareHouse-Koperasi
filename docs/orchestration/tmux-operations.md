# Tmux Operations

The orchestrator relies on two primary tmux sessions:

1. `warehouse-issue-detector`: Runs continuous issue detector polling.
2. `warehouse-composer-dev`: Runs persistent `composer run dev` supervisor.

## Commands
```bash
# Start all sessions
npm run tmux:start

# Stop all sessions
npm run tmux:stop

# Restart sessions
npm run tmux:restart

# Check session status
npm run tmux:status

# Attach to detector
tmux attach -t warehouse-issue-detector

# Attach to composer dev
tmux attach -t warehouse-composer-dev
```
