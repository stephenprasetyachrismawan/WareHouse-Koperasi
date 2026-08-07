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
    // cleanup-worktree.sh takes (worktree_path, branch_name, control_repo) —
    // this call had branch and worktreePath swapped, so its `[ -d
    // "$WORKTREE_PATH" ]` check received a branch name (never a real
    // directory) and silently skipped the actual `git worktree remove` on
    // every single successful job. "Worktree cleanup complete" was printing
    // having done nothing; every worktree in this session was left behind
    // and had to be removed by hand.
    execSync(`bash "${scriptPath}" "${worktreePath}" "${branch}" "${cfg.controlRepository}"`, {
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
    // The control clone only ever mirrors origin/main; a plain `pull` breaks the
    // moment local main diverges from a squash-merged remote history (two
    // branches from a common ancestor). Force it to match origin exactly instead.
    execSync(`git -C "${cfg.controlRepository}" fetch --prune origin`, { stdio: 'inherit' });
    execSync(`git -C "${cfg.controlRepository}" checkout main`, { stdio: 'inherit' });
    execSync(`git -C "${cfg.controlRepository}" reset --hard origin/main`, { stdio: 'inherit' });
    execSync(`git -C "${cfg.controlRepository}" clean -fd`, { stdio: 'inherit' });
  }

  /**
   * A merge can land new migration files, but nothing else ever applies them
   * to control's running database — composer-dev boots "healthy" off a
   * server that 500s the moment a route touches the new table (this is
   * exactly what issue #12 reported). Run explicitly with the same sqlite
   * env override dev-supervisor.sh uses, since control's .env is not
   * guaranteed to match how the live server is actually being run.
   */
  public static runControlMigrations(): void {
    const cfg = ConfigManager.get();
    console.log(`[GitManager] Running pending migrations on control database...`);
    const dbPath = `${cfg.controlRepository}/database/database.sqlite`;
    execSync(`php artisan migrate --force`, {
      cwd: cfg.controlRepository,
      stdio: 'inherit',
      env: { ...process.env, DB_CONNECTION: 'sqlite', DB_DATABASE: dbPath }
    });
  }
}

import path from 'path';
