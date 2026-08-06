import { execSync } from 'child_process';
import { ConfigManager } from '../config';

export class GitManager {
  public static prepareWorktree(branch: string, worktreePath: string, gitName: string, gitEmail: string): void {
    const cfg = ConfigManager.get();
    const scriptPath = path.resolve(__dirname, '../../scripts/prepare-worktree.sh');
    execSync(`bash "${scriptPath}" "${branch}" "${worktreePath}" "${gitName}" "${gitEmail}" "${cfg.controlRepository}"`, {
      stdio: 'inherit'
    });
  }

  public static cleanupWorktree(worktreePath: string, branch: string): void {
    const cfg = ConfigManager.get();
    const scriptPath = path.resolve(__dirname, '../../scripts/cleanup-worktree.sh');
    execSync(`bash "${scriptPath}" "${branch}" "${worktreePath}" "${cfg.controlRepository}"`, {
      stdio: 'inherit'
    });
  }

  public static createCommit(worktreePath: string, commitTitle: string, gitName: string, gitEmail: string): string {
    const status = execSync(`git -C "${worktreePath}" status --porcelain`, { encoding: 'utf-8' }).trim();
    if (status.length > 0) {
      execSync(`git -C "${worktreePath}" config --worktree user.name "${gitName}"`, { stdio: 'inherit' });
      execSync(`git -C "${worktreePath}" config --worktree user.email "${gitEmail}"`, { stdio: 'inherit' });
      execSync(`git -C "${worktreePath}" add -A`, { stdio: 'inherit' });
      execSync(`git -C "${worktreePath}" commit -m "${commitTitle}"`, { stdio: 'inherit' });
    } else {
      console.log(`[GitManager] Working tree clean; using existing HEAD commit.`);
    }
    return execSync(`git -C "${worktreePath}" rev-parse HEAD`, { encoding: 'utf-8' }).trim();
  }

  public static pushBranch(worktreePath: string, branch: string): void {
    console.log(`[GitManager] Pushing branch ${branch} to origin...`);
    execSync(`git -C "${worktreePath}" push origin "${branch}" --force`, { stdio: 'inherit' });
  }

  public static deleteRemoteBranch(branch: string): void {
    const cfg = ConfigManager.get();
    console.log(`[GitManager] Deleting remote branch ${branch}...`);
    try {
      execSync(`git -C "${cfg.controlRepository}" push origin --delete "${branch}"`, { stdio: 'inherit' });
    } catch (e: any) {
      console.warn(`[GitManager] Could not delete remote branch ${branch}: ${e.message}`);
    }
  }

  public static syncControlMain(): void {
    const cfg = ConfigManager.get();
    console.log(`[GitManager] Syncing control main repository...`);
    execSync(`git -C "${cfg.controlRepository}" checkout main`, { stdio: 'inherit' });
    execSync(`git -C "${cfg.controlRepository}" pull origin main`, { stdio: 'inherit' });
  }
}

import path from 'path';
