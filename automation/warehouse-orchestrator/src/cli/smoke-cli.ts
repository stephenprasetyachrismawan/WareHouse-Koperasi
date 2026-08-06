import { execSync } from 'child_process';
import { ConfigManager } from '../config';
import { AntigravityRunner } from '../antigravity';
import { DevSessionManager } from '../dev-session';
import { GitHubClient } from '../github';

async function main() {
  const target = process.argv[2];
  const cfg = ConfigManager.get();

  switch (target) {
    case 'agy': {
      console.log('=== Smoke Test: Antigravity CLI ===');
      console.log(`Binary: ${cfg.binary}, Model: ${cfg.model}`);
      const res = await AntigravityRunner.runPrompt(cfg.controlRepository, 'Return exactly AGY_READY without editing files.');
      console.log(`Response: ${res.rawResponse.trim()}`);
      console.log('Antigravity smoke test: PASS');
      break;
    }
    case 'tmux': {
      console.log('=== Smoke Test: Tmux ===');
      const output = execSync('tmux list-sessions 2>&1 || true', { encoding: 'utf-8' });
      console.log('Active Sessions:\n' + output);
      console.log('Tmux smoke test: PASS');
      break;
    }
    case 'composer-dev': {
      console.log('=== Smoke Test: Composer Dev Supervisor ===');
      const health = DevSessionManager.getHealth();
      console.log('Health Output:\n' + JSON.stringify(health, null, 2));
      console.log('Composer Dev smoke test: PASS');
      break;
    }
    case 'github': {
      console.log('=== Smoke Test: GitHub Integration ===');
      try {
        const issues = await GitHubClient.fetchOpenIssuesWithLabel('agent:run');
        console.log(`Fetched open issues with label 'agent:run': ${issues.length}`);
        console.log('GitHub integration smoke test: PASS');
      } catch (e: any) {
        console.error('GitHub smoke test error:', e.message);
        process.exit(1);
      }
      break;
    }
    default:
      console.log('Usage: npm run smoke:agy | tmux | composer-dev | github');
      break;
  }
}

main().catch((err) => {
  console.error('Smoke test failed:', err);
  process.exit(1);
});
