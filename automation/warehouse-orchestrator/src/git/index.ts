import { execSync } from 'child_process';
import path from 'path';
import fs from 'fs';
import { ConfigManager } from '../config';

export class GitManager {
  public static prepareWorktree(
    branch: string,
    worktreePath: string,
    gitName: string,
    gitEmail: string
  ): void {
    const cfg = ConfigManager.get();
    const scriptPath = path.resolve(__dirname, '../../scripts/prepare-worktree.sh');
    const cmd = `bash "${scriptPath}" "${branch}" "${worktreePath}" "${gitName}" "${gitEmail}" "${cfg.controlRepository}"`;
    execSync(cmd, { stdio: 'inherit' });
  }

  public static cleanupWorktree(worktreePath: string, branch: string): void {
    const cfg = ConfigManager.get();
    const scriptPath = path.resolve(__dirname, '../../scripts/cleanup-worktree.sh');
    const cmd = `bash "${scriptPath}" "${worktreePath}" "${branch}" "${cfg.controlRepository}"`;
    execSync(cmd, { stdio: 'inherit' });
  }

  public static createCommit(
    worktreePath: string,
    message: string,
    gitName: string,
    gitEmail: string
  ): string {
    // Stage modified and untracked files (excluding .agent-runtime and database.sqlite)
    execSync(`git -C "${worktreePath}" add -A`, { stdio: 'pipe' });
    execSync(`git -C "${worktreePath}" reset -- .agent-runtime database/database.sqlite 2>/dev/null || true`, { stdio: 'pipe' });

    // Ensure identity is applied
    execSync(`git -C "${worktreePath}" config --worktree user.name "${gitName}"`, { stdio: 'pipe' });
    execSync(`git -C "${worktreePath}" config --worktree user.email "${gitEmail}"`, { stdio: 'pipe' });

    const env = {
      ...process.env,
      GIT_AUTHOR_NAME: gitName,
      GIT_AUTHOR_EMAIL: gitEmail,
      GIT_COMMITTER_NAME: gitName,
      GIT_COMMITTER_EMAIL: gitEmail
    };

    execSync(`git -C "${worktreePath}" commit -m "${message.replace(/"/g, '\\"')}"`, {
      env,
      stdio: 'pipe'
    });

    const sha = execSync(`git -C "${worktreePath}" rev-parse HEAD`, { encoding: 'utf-8' }).trim();
    return sha;
  }

  public static pushBranch(worktreePath: string, branch: string): void {
    execSync(`git -C "${worktreePath}" push -u origin "${branch}"`, { stdio: 'inherit' });
  }

  public static syncControlMain(): void {
    const cfg = ConfigManager.get();
    const repo = cfg.controlRepository;
    if (fs.existsSync(repo)) {
      execSync(`git -C "${repo}" fetch --prune origin`, { stdio: 'pipe' });
      execSync(`git -C "${repo}" switch main 2>/dev/null || git -C "${repo}" checkout main`, { stdio: 'pipe' });
      execSync(`git -C "${repo}" reset --hard origin/main`, { stdio: 'pipe' });
      execSync(`git -C "${repo}" clean -fd`, { stdio: 'pipe' });
      execSync(`git -C "${repo}" worktree prune`, { stdio: 'pipe' });
    }
  }

  public static deleteRemoteBranch(branch: string): void {
    const cfg = ConfigManager.get();
    try {
      execSync(`git -C "${cfg.controlRepository}" push origin --delete "${branch}"`, { stdio: 'pipe' });
    } catch (e) {
      console.warn(`Could not delete remote branch ${branch}:`, e);
    }
  }
}
