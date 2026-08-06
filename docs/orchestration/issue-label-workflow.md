# Issue Label Workflow State Machine

The orchestrator moves issues through strict sequential label states:

```text
agent:run
  → agent:planning
  → agent:coding
  → agent:testing
  → agent:dev-check
  → agent:pr
  → agent:merging
  → agent:done
```

## Failure & Blocked States
- `agent:blocked`: Configuration or user authorization missing.
- `agent:failed`: Test gate or execution error.
- `agent:identity-authorization-required`: Missing Device Flow token for contributor.
- `agent:post-merge-failed`: PR merged but post-merge main dev health check failed.
- `agent:retry`: Applied by maintainer to resume a failed issue.
