import sqlite3 from 'sqlite3';
import path from 'path';
import fs from 'fs';
import { ConfigManager } from '../config';

export interface JobRecord {
  id?: number;
  issue_number: number;
  issue_author: string;
  trigger_actor: string;
  effective_actor: string;
  base_sha: string;
  branch: string;
  worktree: string;
  state: string;
  attempt: number;
  agent: string;
  model: string;
  effort: string;
  skills: string;
  commit_sha?: string;
  pr_number?: number;
  merge_sha?: string;
  created_at?: string;
  updated_at?: string;
  failure_reason?: string;
}

export class Database {
  private static db: sqlite3.Database;

  public static getDb(): sqlite3.Database {
    if (this.db) return this.db;

    const cfg = ConfigManager.get();
    fs.mkdirSync(cfg.stateDirectory, { recursive: true });
    const dbPath = path.join(cfg.stateDirectory, 'orchestrator.sqlite');

    this.db = new sqlite3.Database(dbPath);
    this.initTables();
    return this.db;
  }

  private static initTables(): void {
    const db = this.db;
    db.serialize(() => {
      db.run(`
        CREATE TABLE IF NOT EXISTS jobs (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          issue_number INTEGER NOT NULL,
          issue_author TEXT NOT NULL,
          trigger_actor TEXT NOT NULL,
          effective_actor TEXT NOT NULL,
          base_sha TEXT NOT NULL,
          branch TEXT NOT NULL,
          worktree TEXT NOT NULL,
          state TEXT NOT NULL,
          attempt INTEGER NOT NULL DEFAULT 1,
          agent TEXT NOT NULL,
          model TEXT NOT NULL,
          effort TEXT NOT NULL,
          skills TEXT NOT NULL,
          commit_sha TEXT,
          pr_number INTEGER,
          merge_sha TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          failure_reason TEXT
        );
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS job_events (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          job_id INTEGER NOT NULL,
          checkpoint TEXT NOT NULL,
          message TEXT NOT NULL,
          timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY(job_id) REFERENCES jobs(id)
        );
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS user_tokens (
          github_username TEXT PRIMARY KEY,
          access_token TEXT NOT NULL,
          refresh_token TEXT,
          expires_at INTEGER,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
      `);

      db.run(`
        CREATE TABLE IF NOT EXISTS system_state (
          key TEXT PRIMARY KEY,
          value TEXT NOT NULL,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
      `);
    });
  }

  public static async createJob(job: JobRecord): Promise<number> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      const stmt = db.prepare(`
        INSERT INTO jobs (issue_number, issue_author, trigger_actor, effective_actor, base_sha, branch, worktree, state, attempt, agent, model, effort, skills)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `);
      stmt.run(
        job.issue_number,
        job.issue_author,
        job.trigger_actor,
        job.effective_actor,
        job.base_sha,
        job.branch,
        job.worktree,
        job.state,
        job.attempt,
        job.agent,
        job.model,
        job.effort,
        job.skills,
        function (this: sqlite3.RunResult, err: Error | null) {
          if (err) return reject(err);
          resolve(this.lastID);
        }
      );
      stmt.finalize();
    });
  }

  public static async updateJobState(jobId: number, state: string, failureReason?: string): Promise<void> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      const stmt = db.prepare(`
        UPDATE jobs
        SET state = ?, failure_reason = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
      `);
      stmt.run(state, failureReason || null, jobId, (err: Error | null) => {
        if (err) return reject(err);
        resolve();
      });
      stmt.finalize();
    });
  }

  public static async getActiveJobForIssue(issueNumber: number): Promise<JobRecord | null> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      db.get(
        `SELECT * FROM jobs WHERE issue_number = ? AND state NOT IN ('agent:done', 'agent:failed', 'agent:blocked', 'agent:post-merge-failed') ORDER BY id DESC LIMIT 1`,
        [issueNumber],
        (err: Error | null, row: any) => {
          if (err) return reject(err);
          resolve((row as JobRecord) || null);
        }
      );
    });
  }

  public static async addJobEvent(jobId: number, checkpoint: string, message: string): Promise<void> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      const stmt = db.prepare(`INSERT INTO job_events (job_id, checkpoint, message) VALUES (?, ?, ?)`);
      stmt.run(jobId, checkpoint, message, (err: Error | null) => {
        if (err) return reject(err);
        resolve();
      });
      stmt.finalize();
    });
  }

  public static async saveToken(username: string, accessToken: string, refreshToken?: string, expiresAt?: number): Promise<void> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      const stmt = db.prepare(`
        INSERT INTO user_tokens (github_username, access_token, refresh_token, expires_at, updated_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON CONFLICT(github_username) DO UPDATE SET
          access_token = excluded.access_token,
          refresh_token = excluded.refresh_token,
          expires_at = excluded.expires_at,
          updated_at = CURRENT_TIMESTAMP
      `);
      stmt.run(username, accessToken, refreshToken || null, expiresAt || null, (err: Error | null) => {
        if (err) return reject(err);
        resolve();
      });
      stmt.finalize();
    });
  }

  public static async getToken(username: string): Promise<{ access_token: string; refresh_token?: string; expires_at?: number } | null> {
    const db = this.getDb();
    return new Promise((resolve, reject) => {
      db.get(`SELECT access_token, refresh_token, expires_at FROM user_tokens WHERE github_username = ?`, [username], (err: Error | null, row: any) => {
        if (err) return reject(err);
        resolve((row as any) || null);
      });
    });
  }
}
