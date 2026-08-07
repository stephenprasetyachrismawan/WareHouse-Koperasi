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

    console.log(`[DevSession] Pointed supervisor to ${targetWorktree}. Waiting ${warmupSeconds}s for warm-up...`);

    // Poll health status during warm-up
    for (let i = 0; i < warmupSeconds; i++) {
      await new Promise((r) => setTimeout(r, 1000));
    }

    const health = this.getHealth();
    if (!health) {
      throw new Error('Composer development supervisor health state file not found');
    }

    if (health.status !== 'HEALTHY') {
      throw new Error(`Composer development supervisor check failed with status: ${health.status}`);
    }

    return health;
  }
}
