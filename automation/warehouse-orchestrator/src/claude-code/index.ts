import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { ConfigManager } from '../config';

const RAW_OUTPUT_TAIL_CHARS = 2000;

export interface AgentResult {
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

/**
 * Tools the coding agent must never use directly; publishing, merging, and
 * system administration stay with the deterministic orchestrator (GitManager /
 * GitHubClient), matching .agent/ORCHESTRATION.md section 5.
 */
const DISALLOWED_TOOLS = [
  'Bash(git push*)',
  'Bash(git reset --hard*)',
  'Bash(git clean*)',
  'Bash(gh pr*)',
  'Bash(gh issue*)',
  'Bash(sudo*)',
  'Bash(composer run dev*)',
  'Bash(php artisan serve*)',
  'Bash(npm run dev*)',
  'Bash(systemctl*)'
];

export class ClaudeCodeRunner {
  /**
   * Headless no-edit ping used by doctor/config-cli in place of Antigravity's
   * `agy models` listing, which has no Claude Code CLI equivalent.
   */
  public static async smokeTest(cwd: string): Promise<boolean> {
    try {
      const { parsedResult, rawResponse } = await this.runPrompt(
        cwd,
        'Return exactly the text CLAUDE_READY without editing any files.',
        undefined,
        30
      );
      void parsedResult;
      return rawResponse.trim() === 'CLAUDE_READY';
    } catch {
      return false;
    }
  }

  public static async runPrompt(
    worktreePath: string,
    prompt: string,
    onProgress?: (event: any) => void,
    timeoutSecondsOverride?: number
  ): Promise<{ rawResponse: string; parsedResult: AgentResult | null }> {
    const cfg = ConfigManager.get();
    const timeoutSeconds = timeoutSecondsOverride ?? cfg.timeoutSeconds;

    return new Promise((resolve, reject) => {
      const args = ['-p', prompt, '--model', cfg.model, '--output-format', 'stream-json', '--verbose', '--dangerously-skip-permissions'];

      if (cfg.effort) {
        args.push('--effort', cfg.effort);
      }
      args.push('--disallowedTools', ...DISALLOWED_TOOLS);

      const child = spawn(cfg.binary, args, {
        cwd: worktreePath,
        env: { ...process.env },
        stdio: ['ignore', 'pipe', 'pipe']
      });

      let rawOutput = '';
      let errorOutput = '';
      let finalResponseText = '';
      let isError = false;

      const timeoutTimer = setTimeout(() => {
        child.kill('SIGTERM');
        reject(new Error(`Claude Code execution timed out after ${timeoutSeconds} seconds`));
      }, timeoutSeconds * 1000);

      child.stdout.on('data', (chunk: Buffer) => {
        const str = chunk.toString('utf-8');
        rawOutput += str;

        const lines = str.split('\n');
        for (const line of lines) {
          if (!line.trim()) continue;
          try {
            const parsed = JSON.parse(line.trim());
            if (onProgress) onProgress(parsed);

            if (parsed.type === 'result') {
              finalResponseText = typeof parsed.result === 'string' ? parsed.result : '';
              isError = Boolean(parsed.is_error);
            }
          } catch (e) {
            // Non-JSON output line
          }
        }
      });

      child.stderr.on('data', (chunk: Buffer) => {
        errorOutput += chunk.toString('utf-8');
      });

      child.on('close', (code, signal) => {
        clearTimeout(timeoutTimer);

        // Always keep the full transcript on disk — it can be hundreds of KB
        // and must never be embedded whole in an error message, job_events
        // row, or GitHub comment (that blew a real run's comment past
        // GitHub's size limit and masked the underlying failure).
        let logPath: string | null = null;
        try {
          const logDir = path.join(worktreePath, '.agent-runtime', 'logs');
          fs.mkdirSync(logDir, { recursive: true });
          logPath = path.join(logDir, 'claude-code-raw.log');
          fs.writeFileSync(logPath, rawOutput, 'utf-8');
        } catch (e) {
          console.warn('[ClaudeCodeRunner] Could not persist raw output log:', e);
        }

        if ((code !== 0 || isError) && !finalResponseText) {
          const tail = (errorOutput || rawOutput).slice(-RAW_OUTPUT_TAIL_CHARS);
          const killedBySignal = code === null && signal;
          const cause = killedBySignal
            ? `terminated by signal ${signal} (likely transient — rate limit boundary or resource pressure; consider agent:retry)`
            : `exited with code ${code}`;
          return reject(
            new Error(`Claude Code ${cause}.\nLast ${RAW_OUTPUT_TAIL_CHARS} chars of output:\n${tail}\nFull log: ${logPath ?? '(not written)'}`)
          );
        }
        if (isError) {
          return reject(new Error(`Claude Code reported an error: ${finalResponseText}`));
        }

        let parsedResult: AgentResult | null = null;
        try {
          const jsonMatch = finalResponseText.match(/\{[\s\S]*\}/);
          if (jsonMatch) {
            parsedResult = JSON.parse(jsonMatch[0]);
          }
        } catch (e) {
          console.warn('Could not parse JSON result from Claude Code output:', e);
        }

        resolve({ rawResponse: finalResponseText || rawOutput, parsedResult });
      });
    });
  }
}
