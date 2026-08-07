import fs from 'fs';
import path from 'path';
import { ConfigManager } from '../config';

export interface DevHealthStatus {
  timestamp: string;
  status: string;
  composerPid: number;
  targetWorktree: string;
  restartCount: number;
  fatalLogCount: number;
}

export class DevSessionManager {
  private static stateDir = '/srv/warehouse-koperasi/state';
  private static targetFile = '/srv/warehouse-koperasi/state/active-dev-worktree';
  private static healthFile = '/srv/warehouse-koperasi/state/dev-health.json';

  public static setActiveTarget(worktreePath: string): void {
    fs.mkdirSync(this.stateDir, { recursive: true });
    fs.writeFileSync(this.targetFile, worktreePath, 'utf-8');
  }

  public static getHealth(): DevHealthStatus | null {
    if (!fs.existsSync(this.healthFile)) {
      return null;
    }
    try {
      const raw = fs.readFileSync(this.healthFile, 'utf-8');
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  public static async waitForWarmupAndVerify(targetWorktree: string, warmupSeconds: number = 45): Promise<DevHealthStatus> {
    this.setActiveTarget(targetWorktree);

    console.log(`[DevSession] Pointed supervisor to ${targetWorktree}. Waiting up to ${warmupSeconds}s for HEALTHY...`);

    // The supervisor script only checks for a target change every 5s, then
    // needs ~3s to tear down the previous process group and ~10s of its own
    // internal warm-up before it reports HEALTHY — a fixed sleep-then-check-
    // once here raced that and failed on a target still reporting STARTING.
    // Poll instead: succeed the moment HEALTHY appears, only give up once
    // the full budget elapses.
    let health: DevHealthStatus | null = null;
    const deadline = Date.now() + warmupSeconds * 1000;
    while (Date.now() < deadline) {
      health = this.getHealth();
      if (health?.status === 'HEALTHY' && health.targetWorktree === targetWorktree) {
        return health;
      }
      await new Promise((r) => setTimeout(r, 2000));
    }

    health = this.getHealth();
    if (!health) {
      throw new Error('Composer development supervisor health state file not found');
    }

    if (health.status !== 'HEALTHY' || health.targetWorktree !== targetWorktree) {
      throw new Error(`Composer development supervisor check failed with status: ${health.status}`);
    }

    return health;
  }
}
