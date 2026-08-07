import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { ConfigManager } from '../config';
import { AntigravityRunner } from '../antigravity';
import { IdentityManager } from '../identity';

async function runDoctor() {
  console.log('=====================================================');
  console.log(' WAREHOUSE-KOPERASI ORCHESTRATOR DOCTOR CHECK');
  console.log('=====================================================\n');

  const cfg = ConfigManager.get();
  let allPass = true;
  const repoRoot = path.resolve(__dirname, '../../../../');

  function check(name: string, fn: () => boolean | string) {
    try {
      const res = fn();
      if (res === true || typeof res === 'string') {
        console.log(`[PASS] ${name} ${typeof res === 'string' ? `(${res})` : ''}`);
      } else {
        console.log(`[FAIL] ${name}`);
        allPass = false;
      }
    } catch (e: any) {
      console.log(`[FAIL] ${name}: ${e.message}`);
      allPass = false;
    }
  }

  check('Git CLI installed', () => execSync('git --version', { encoding: 'utf-8' }).trim());
  check('Tmux installed', () => execSync('tmux -V', { encoding: 'utf-8' }).trim());
  check('PHP installed', () => execSync('php -v', { encoding: 'utf-8' }).split('\n')[0]);
  check('Composer installed', () => execSync('composer --version', { encoding: 'utf-8' }).split('\n')[0]);
  check('Node.js installed', () => process.version);
  check('Antigravity CLI (agy) installed', () => execSync(`${cfg.binary} --version`, { encoding: 'utf-8' }).trim());

  check('Antigravity Model available', () => {
    const isAvailable = AntigravityRunner.isModelAvailable(cfg.model);
    if (!isAvailable) {
      const models = AntigravityRunner.listAvailableModels();
      throw new Error(`Model '${cfg.model}' not listed. Available: ${models.join(', ')}`);
    }
    return cfg.model;
  });

  check('Custom Agent file exists', () => {
    const agentPath = path.join(repoRoot, '.agents/agents/warehouse-laravel/agent.md');
    if (!fs.existsSync(agentPath)) throw new Error(`Agent file missing at ${agentPath}`);
    return agentPath;
  });

  check('Mandatory Skills exist', () => {
    const base = path.join(repoRoot, '.agents/skills');
    const skills = ['laravel-boost', 'test-driven-development', 'code-simplification'];
    for (const s of skills) {
      if (!fs.existsSync(path.join(base, s, 'SKILL.md'))) {
        throw new Error(`Skill ${s} missing SKILL.md`);
      }
    }
    return skills.join(', ');
  });

  check('Fixed Command Wrappers exist', () => {
    const wrappersDir = path.resolve(__dirname, '../../agent-tools');
    const required = [
      'agent-preflight',
      'agent-baseline',
      'agent-tdd-red',
      'agent-tdd-green',
      'agent-test-focused',
      'agent-test-unit',
      'agent-test-php',
      'agent-test-database',
      'agent-format',
      'agent-static-analysis',
      'agent-final-test',
      'agent-dev-health',
      'agent-git-status',
      'agent-diff-summary'
    ];
    for (const w of required) {
      if (!fs.existsSync(path.join(wrappersDir, w))) {
        throw new Error(`Wrapper ${w} missing`);
      }
    }
    return `${required.length} wrappers verified`;
  });

  check('Team Member Identity Authorization', () => {
    const reg = IdentityManager.getRegistry();
    const members = Object.keys(reg.members);
    return `Members registered: ${members.join(', ')}`;
  });

  console.log('\n=====================================================');
  if (allPass) {
    console.log(' ALL DOCTOR PREFLIGHT CHECKS PASSED SUCCESSFULLY!');
  } else {
    console.log(' DOCTOR PREFLIGHT CHECKS FAILED. Please resolve errors.');
  }
  console.log('=====================================================\n');
}

runDoctor();
