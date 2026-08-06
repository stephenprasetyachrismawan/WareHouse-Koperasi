import fs from 'fs';
import path from 'path';
import dotenv from 'dotenv';

dotenv.config();

export interface RuntimeConfig {
  binary: string;
  agent: string;
  model: string;
  effort: string;
  outputFormat: string;
  executionMode: string;
  timeoutSeconds: number;
  maxAttempts: number;
  subagents: boolean;
  enabled: boolean;
  pollIntervalSeconds: number;
  controlRepository: string;
  worktreesDirectory: string;
  stateDirectory: string;
  logsDirectory: string;
  credentialsDirectory: string;
  githubRepository: string;
}

const CONFIG_PATH = path.resolve(__dirname, '../../config/orchestrator-config.json');

export class ConfigManager {
  private static config: RuntimeConfig;

  public static load(): RuntimeConfig {
    let baseConfig: Partial<RuntimeConfig> = {};
    if (fs.existsSync(CONFIG_PATH)) {
      try {
        const raw = fs.readFileSync(CONFIG_PATH, 'utf-8');
        baseConfig = JSON.parse(raw);
      } catch (err) {
        console.error('Error reading orchestrator-config.json:', err);
      }
    }

    const config: RuntimeConfig = {
      binary: process.env.AGY_BIN || baseConfig.binary || 'agy',
      agent: process.env.AGY_AGENT || baseConfig.agent || 'warehouse-laravel',
      model: process.env.AGY_MODEL || baseConfig.model || 'gemini-3.6-flash-low',
      effort: process.env.AGY_EFFORT || baseConfig.effort || 'low',
      outputFormat: process.env.AGY_OUTPUT_FORMAT || baseConfig.outputFormat || 'stream-json',
      executionMode: process.env.AGY_EXECUTION_MODE || baseConfig.executionMode || 'accept-edits',
      timeoutSeconds: Number(process.env.AGY_TIMEOUT_SECONDS) || baseConfig.timeoutSeconds || 1800,
      maxAttempts: Number(process.env.AGY_MAX_ATTEMPTS) || baseConfig.maxAttempts || 2,
      subagents: false,
      enabled: process.env.ORCHESTRATOR_ENABLED === 'true' || baseConfig.enabled || false,
      pollIntervalSeconds: Number(process.env.POLL_INTERVAL_SECONDS) || baseConfig.pollIntervalSeconds || 30,
      controlRepository: process.env.CONTROL_REPOSITORY || baseConfig.controlRepository || '/srv/warehouse-koperasi/control',
      worktreesDirectory: process.env.WORKTREES_DIRECTORY || baseConfig.worktreesDirectory || '/srv/warehouse-koperasi/worktrees',
      stateDirectory: process.env.STATE_DIRECTORY || baseConfig.stateDirectory || '/srv/warehouse-koperasi/state',
      logsDirectory: process.env.LOGS_DIRECTORY || baseConfig.logsDirectory || '/srv/warehouse-koperasi/logs',
      credentialsDirectory: process.env.CREDENTIALS_DIRECTORY || baseConfig.credentialsDirectory || '/srv/warehouse-koperasi/credentials',
      githubRepository: process.env.GITHUB_REPOSITORY || baseConfig.githubRepository || 'stephenprasetyachrismawan/WareHouse-Koperasi'
    };

    this.config = config;
    return config;
  }

  public static get(): RuntimeConfig {
    if (!this.config) {
      return this.load();
    }
    return this.config;
  }

  public static updateConfig(updates: Partial<RuntimeConfig>): RuntimeConfig {
    const current = this.load();
    const updated = { ...current, ...updates };
    fs.writeFileSync(CONFIG_PATH, JSON.stringify(updated, null, 2), 'utf-8');
    this.config = updated;
    return updated;
  }

  public static setEnabled(enabled: boolean): RuntimeConfig {
    return this.updateConfig({ enabled });
  }
}
