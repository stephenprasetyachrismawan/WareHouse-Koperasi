import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { ConfigManager } from '../config';
import { ClaudeCodeRunner } from '../claude-code';
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
  check('Claude Code CLI installed', () => execSync(`${cfg.binary} --version`, { encoding: 'utf-8' }).trim());

  const agentReady = await ClaudeCodeRunner.smokeTest(cfg.controlRepository);
  check('Coding agent smoke test', () => {
    if (!agentReady) {
      throw new Error(`No-edit smoke prompt against model '${cfg.model}' did not return the expected response`);
    }
    return cfg.model;
  });

  check('CLAUDE.md project instructions exist', () => {
    const claudeMdPath = path.join(repoRoot, 'CLAUDE.md');
    if (!fs.existsSync(claudeMdPath)) throw new Error(`CLAUDE.md missing at ${claudeMdPath}`);
    return claudeMdPath;
  });

  check('Project skills available to Claude Code', () => {
    const base = path.join(repoRoot, '.claude/skills');
    const skills = ['laravel-best-practices', 'pest-testing'];
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
