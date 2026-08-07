import { ConfigManager } from '../src/config';
import { IdentityManager } from '../src/identity';
import { ClaudeCodeRunner } from '../src/claude-code';
import { LabelStateMachine } from '../src/labels';
import { DevSessionManager } from '../src/dev-session';
import path from 'path';
import fs from 'fs';

function assert(condition: boolean, message: string) {
  if (!condition) {
    console.error(`❌ ASSERTION FAILED: ${message}`);
    throw new Error(message);
  } else {
    console.log(`  ✓ ${message}`);
  }
}

async function runTests() {
  console.log('=====================================================');
  console.log(' RUNNING AUTOMATED SUITE FOR WAREHOUSE ORCHESTRATOR');
  console.log('=====================================================\n');

  console.log('[Test 1] Runtime Configuration');
  const cfg = ConfigManager.get();
  assert(cfg.binary === 'claude', 'Binary defaults to claude');
  assert(cfg.agent === 'claude-code', 'Agent label defaults to claude-code');
  assert(cfg.model === 'claude-sonnet-5', 'Model defaults to claude-sonnet-5');
  assert(cfg.effort === 'low', 'Effort defaults to low');

  console.log('\n[Test 2] Contributor Identity Resolution');
  const reg = IdentityManager.getRegistry();
  assert(reg.members['stephenprasetyachrismawan'] !== undefined, 'Stephen is registered');
  assert(reg.members['wiladahtulawaliah2002-maker'] !== undefined, 'Wiladah is registered');
  assert(reg.members['zhafirzidann'] !== undefined, 'Zhafir is registered');

  const res1 = IdentityManager.resolveEffectiveActor('zhafirzidann', 'stephenprasetyachrismawan', 'write');
  assert(res1.effectiveActor === 'zhafirzidann', 'Registered author with write permission resolves to self');

  const res2 = IdentityManager.resolveEffectiveActor('unknown-external-user', 'stephenprasetyachrismawan', 'none');
  assert(res2.effectiveActor === 'stephenprasetyachrismawan', 'External author resolves to repository owner');

  console.log('\n[Test 3] Label State Machine');
  assert(LabelStateMachine.ALL_WORKFLOW_LABELS.includes('agent:run'), 'Includes agent:run');
  assert(LabelStateMachine.ALL_WORKFLOW_LABELS.includes('agent:planning'), 'Includes agent:planning');
  assert(LabelStateMachine.ALL_WORKFLOW_LABELS.includes('agent:done'), 'Includes agent:done');
  assert(LabelStateMachine.ALL_WORKFLOW_LABELS.includes('agent:post-merge-failed'), 'Includes agent:post-merge-failed');

  console.log('\n[Test 4] Claude Code Smoke Test');
  const isReady = await ClaudeCodeRunner.smokeTest(cfg.controlRepository);
  assert(isReady === true, `Configured model ${cfg.model} responds to a no-edit smoke prompt`);

  console.log('\n[Test 5] Fixed Command Wrappers');
  const wrapperDir = path.resolve(__dirname, '../agent-tools');
  const wrappers = ['agent-preflight', 'agent-baseline', 'agent-tdd-red', 'agent-tdd-green', 'agent-test-focused', 'agent-dev-health'];
  for (const w of wrappers) {
    assert(fs.existsSync(path.join(wrapperDir, w)), `Wrapper ${w} exists`);
  }

  console.log('\n[Test 6] Dev Session Health File');
  DevSessionManager.setActiveTarget('/tmp');
  const targetContent = fs.readFileSync('/srv/warehouse-koperasi/state/active-dev-worktree', 'utf-8');
  assert(targetContent === '/tmp', 'DevSession target file updated correctly');
  DevSessionManager.setActiveTarget(cfg.controlRepository);

  console.log('\n=====================================================');
  console.log(' ALL 6 ORCHESTRATOR UNIT & INTEGRATION TESTS PASSED!');
  console.log('=====================================================\n');
}

runTests().catch((err) => {
  console.error('Test suite failed:', err);
  process.exit(1);
});
