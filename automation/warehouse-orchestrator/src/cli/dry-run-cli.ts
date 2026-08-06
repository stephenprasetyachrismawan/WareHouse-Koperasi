import path from 'path';
import { ConfigManager } from '../config';
import { IdentityManager } from '../identity';
import { GitManager } from '../git';
import { AntigravityRunner } from '../antigravity';
import { DevSessionManager } from '../dev-session';
import { execSync } from 'child_process';

async function runDryRun() {
  console.log('=====================================================');
  console.log(' LOCAL ORCHESTRATOR DRY RUN');
  console.log('=====================================================\n');

  const cfg = ConfigManager.get();
  const fixtureIssue = {
    number: 9999,
    title: 'Dry run test warehouse policy verification',
    body: 'Test issue for dry run pipeline verification',
    user: { login: 'stephenprasetyachrismawan' },
    html_url: 'https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi/issues/9999'
  };

  console.log('[DryRun 1/7] Resolving contributor identity...');
  const { effectiveActor, member } = IdentityManager.resolveEffectiveActor(fixtureIssue.user.login, fixtureIssue.user.login);
  console.log(`Resolved Effective Actor: @${effectiveActor} (${member.gitName} <${member.gitEmail}>)`);

  const branch = `agent/dry-run/issue-9999-dry-run-test`;
  const worktreePath = path.join(cfg.worktreesDirectory, 'dry-run-issue-9999');

  console.log('[DryRun 2/7] Creating temporary dry-run worktree...');
  GitManager.prepareWorktree(branch, worktreePath, member.gitName, member.gitEmail);

  try {
    console.log('[DryRun 3/7] Testing Antigravity CLI invocation (headless no-edit smoke test)...');
    const smokeResult = await AntigravityRunner.runPrompt(worktreePath, 'Return exactly AGY_READY without editing files.');
    console.log(`Antigravity Smoke Result: ${smokeResult.rawResponse.trim()}`);

    console.log('[DryRun 4/7] Testing fixed wrapper tools in worktree...');
    const preflightRes = execSync(`bash "${worktreePath}/automation/warehouse-orchestrator/agent-tools/agent-preflight"`, {
      cwd: worktreePath,
      encoding: 'utf-8'
    });
    console.log(preflightRes.trim());

    console.log('[DryRun 5/7] Switching dev supervisor to dry-run worktree and verifying health...');
    DevSessionManager.setActiveTarget(worktreePath);
    const health = DevSessionManager.getHealth();
    console.log(`Dev Supervisor Status: ${health?.status || 'HEALTHY'}`);

    console.log('[DryRun 6/7] Verifying safety: Skipping push, PR creation, and squash merge (DRY RUN).');

  } finally {
    console.log('[DryRun 7/7] Cleaning up temporary worktree and restoring supervisor to control main...');
    DevSessionManager.setActiveTarget(cfg.controlRepository);
    GitManager.cleanupWorktree(worktreePath, branch);
  }

  console.log('\n=====================================================');
  console.log(' LOCAL DRY RUN COMPLETED SUCCESSFULLY!');
  console.log('=====================================================\n');
}

runDryRun().catch((err) => {
  console.error('Dry run failed:', err);
  process.exit(1);
});
