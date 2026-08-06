import { spawn, execSync } from 'child_process';
import path from 'path';
import fs from 'fs';
import { ConfigManager } from '../config';

export interface AntigravityResult {
  status: string;
  issueNumber: number;
  summary: string;
  planCompleted: boolean;
  skillsUsed: string[];
  additionalSkillsUsed: string[];
  tdd: {
    required: boolean;
    red: { command: string; result: string };
    green: { command: string; result: string };
    refactor: { result: string };
  };
  testsRequested: string[];
  filesChanged: string[];
  databaseImpact: string;
  securityImpact: string;
  suggestedCommitMessage: string;
  pullRequestTitle: string;
  rollback: string;
  remainingRisks: string[];
}

export class AntigravityRunner {
  public static listAvailableModels(): string[] {
    const cfg = ConfigManager.get();
    try {
      const output = execSync(`${cfg.binary} models`, { encoding: 'utf-8', timeout: 15000 });
      const lines = output.split('\n');
      const models: string[] = [];
      for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed && !trimmed.includes('Fetching available models') && !trimmed.includes('---')) {
          const parts = trimmed.split(/\s+/);
          if (parts[0]) models.push(parts[0]);
        }
      }
      return models;
    } catch (err) {
      console.error('Error fetching available agy models:', err);
      return [];
    }
  }

  public static isModelAvailable(modelName: string): boolean {
    const models = this.listAvailableModels();
    if (models.length === 0) return true; // If CLI output unavailable, proceed to let CLI validate
    return models.includes(modelName);
  }

  public static async runPrompt(
    worktreePath: string,
    prompt: string,
    onProgress?: (event: any) => void
  ): Promise<{ rawResponse: string; parsedResult: AntigravityResult | null }> {
    const cfg = ConfigManager.get();

    return new Promise((resolve, reject) => {
      const args = [
        '-p', prompt,
        '--model', cfg.model,
        '--output-format', 'stream-json',
        '--dangerously-skip-permissions'
      ];

      if (cfg.effort) {
        args.push('--effort', cfg.effort);
      }
      if (cfg.agent) {
        args.push('--agent', cfg.agent);
      }

      const child = spawn(cfg.binary, args, {
        cwd: worktreePath,
        env: { ...process.env },
        stdio: ['ignore', 'pipe', 'pipe']
      });

      let rawOutput = '';
      let errorOutput = '';
      let finalResponseText = '';

      const timeoutTimer = setTimeout(() => {
        child.kill('SIGTERM');
        reject(new Error(`Antigravity execution timed out after ${cfg.timeoutSeconds} seconds`));
      }, cfg.timeoutSeconds * 1000);

      child.stdout.on('data', (chunk: Buffer) => {
        const str = chunk.toString('utf-8');
        rawOutput += str;

        const lines = str.split('\n');
        for (const line of lines) {
          if (!line.trim()) continue;
          try {
            const parsed = JSON.parse(line.trim());
            if (onProgress) onProgress(parsed);

            if (parsed.event === 'result' && parsed.result?.response) {
              finalResponseText = parsed.result.response;
            } else if (parsed.event === 'step_update' && parsed.step_update?.text_delta) {
              finalResponseText += parsed.step_update.text_delta;
            }
          } catch (e) {
            // Non-JSON output line
          }
        }
      });

      child.stderr.on('data', (chunk: Buffer) => {
        errorOutput += chunk.toString('utf-8');
      });

      child.on('close', (code) => {
        clearTimeout(timeoutTimer);
        if (code !== 0 && !finalResponseText) {
          return reject(new Error(`Antigravity exited with code ${code}: ${errorOutput || rawOutput}`));
        }

        let parsedResult: AntigravityResult | null = null;
        try {
          const jsonMatch = finalResponseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            parsedResult = JSON.parse(jsonMatch[0]);
          }
        } catch (e) {
          console.warn('Could not parse JSON result from Antigravity output:', e);
        }

        resolve({ rawResponse: finalResponseText || rawOutput, parsedResult });
      });
    });
  }
}
