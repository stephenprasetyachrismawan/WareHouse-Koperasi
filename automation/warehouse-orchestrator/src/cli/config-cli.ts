import { ConfigManager } from '../config';
import { ClaudeCodeRunner } from '../claude-code';

const command = process.argv[2];

function parseArgs(): Record<string, string> {
  const args: Record<string, string> = {};
  for (let i = 3; i < process.argv.length; i++) {
    if (process.argv[i].startsWith('--')) {
      const key = process.argv[i].replace(/^--/, '');
      const val = process.argv[i + 1] && !process.argv[i + 1].startsWith('--') ? process.argv[i + 1] : 'true';
      args[key] = val;
    }
  }
  return args;
}

async function main() {
  switch (command) {
    case 'show':
      console.log('=== Current Orchestrator Configuration ===');
      console.log(JSON.stringify(ConfigManager.get(), null, 2));
      break;

    case 'set-model': {
      const args = parseArgs();
      if (!args.model) {
        console.error('Usage: npm run config:set-model -- --model <model-slug> [--effort low|medium|high]');
        process.exit(1);
      }
      const updates: any = { model: args.model };
      if (args.effort) updates.effort = args.effort;
      const updated = ConfigManager.updateConfig(updates);
      console.log(`Updated model configuration to: ${updated.model} (effort: ${updated.effort})`);
      break;
    }

    case 'set-agent': {
      const args = parseArgs();
      if (!args.agent) {
        console.error('Usage: npm run config:set-agent -- --agent <agent-label>');
        process.exit(1);
      }
      const updated = ConfigManager.updateConfig({ agent: args.agent });
      console.log(`Updated agent label to: ${updated.agent}`);
      break;
    }

    case 'validate': {
      const cfg = ConfigManager.get();
      console.log(`Validating configuration: binary=${cfg.binary}, model=${cfg.model}, effort=${cfg.effort}...`);
      const isReady = await ClaudeCodeRunner.smokeTest(cfg.controlRepository);
      if (isReady) {
        console.log(`[VALID] Model '${cfg.model}' responded correctly to a no-edit smoke prompt.`);
      } else {
        console.error(`[INVALID] Model '${cfg.model}' failed the no-edit smoke prompt.`);
        process.exit(1);
      }
      break;
    }

    default:
      console.log('Usage: npm run config:show | set-model | set-agent | validate');
      break;
  }
}

main().catch((err) => {
  console.error('config-cli failed:', err);
  process.exit(1);
});
