import { ConfigManager } from '../config';

const cmd = process.argv[2];

switch (cmd) {
  case 'enable': {
    const updated = ConfigManager.setEnabled(true);
    console.log(`Orchestrator ENABLED (ORCHESTRATOR_ENABLED=${updated.enabled})`);
    break;
  }
  case 'disable': {
    const updated = ConfigManager.setEnabled(false);
    console.log(`Orchestrator DISABLED (ORCHESTRATOR_ENABLED=${updated.enabled})`);
    break;
  }
  case 'status': {
    const cfg = ConfigManager.get();
    console.log(`=== Orchestrator Status ===`);
    console.log(`Enabled: ${cfg.enabled}`);
    console.log(`Binary: ${cfg.binary}`);
    console.log(`Model: ${cfg.model}`);
    console.log(`Agent: ${cfg.agent}`);
    console.log(`Poll Interval: ${cfg.pollIntervalSeconds}s`);
    break;
  }
  default:
    console.log('Usage: npm run orchestrator:enable | disable | status');
    break;
}
