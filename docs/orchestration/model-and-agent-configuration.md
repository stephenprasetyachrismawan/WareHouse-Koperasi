# Model and Agent Configuration

The model and agent settings are dynamically configured in `automation/warehouse-orchestrator/config/orchestrator-config.json`.

## Operational Commands
```bash
# View current configuration
npm run config:show

# Set model and effort
npm run config:set-model -- --model gemini-3.6-flash-low --effort low

# Set custom agent
npm run config:set-agent -- --agent warehouse-laravel

# Validate model against agy models listing
npm run config:validate
```
