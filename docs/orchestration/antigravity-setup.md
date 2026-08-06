# Antigravity CLI Setup

## Installation Verification
Verify that `agy` is available in PATH:
```bash
agy --version
agy models
```

## Workspace Configuration
- Custom Agent: `.agents/agents/warehouse-laravel/agent.md`
- Fixed Skills: `.agents/skills/{laravel-boost,test-driven-development,code-simplification}/SKILL.md`
- Command Policy Rules: `.agents/rules/command-policy.md`
- Hooks: `.agents/hooks.json`

## Invocation Flags
The orchestrator invokes `agy` in headless mode:
```bash
agy \
  -p "<prompt-packet>" \
  --model gemini-3.6-flash-low \
  --effort low \
  --agent warehouse-laravel \
  --output-format stream-json \
  --dangerously-skip-permissions
```
