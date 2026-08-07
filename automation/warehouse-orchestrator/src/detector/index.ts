import { ConfigManager } from '../config';
import { GitHubClient } from '../github';
import { Database } from '../database';
import { JobRunner } from '../jobs';

export class DetectorService {
  private static isPolling = false;

  public static async pollOnce(): Promise<void> {
    const cfg = ConfigManager.get();

    if (!cfg.enabled) {
      console.log(`[Detector] Orchestrator disabled (ORCHESTRATOR_ENABLED=false). Skipping polling cycle.`);
      return;
    }

    if (this.isPolling) {
      console.log(`[Detector] Polling cycle already in progress.`);
      return;
    }

    this.isPolling = true;

    try {
      console.log(`[Detector] Polling GitHub for issues with label 'agent:run'...`);
      const issues = await GitHubClient.fetchOpenIssuesWithLabel('agent:run');

      if (issues.length === 0) {
        console.log(`[Detector] No issues with label 'agent:run' found.`);
        return;
      }

      console.log(`[Detector] Found ${issues.length} issue(s) with 'agent:run'.`);

      // Process oldest issue first
      for (const issue of issues) {
        const activeJob = await Database.getActiveJobForIssue(issue.number);
        if (activeJob) {
          console.log(`[Detector] Issue #${issue.number} already has active job #${activeJob.id} (${activeJob.state}). Skipping.`);
          continue;
        }

        // Determine trigger actor (or fallback to issue author)
        const triggerActor = issue.user?.login || 'unknown';

        console.log(`[Detector] Starting execution for Issue #${issue.number}...`);
        await JobRunner.executeIssue(issue, triggerActor);
        break; // Only process one issue per cycle
      }
    } catch (err) {
      console.error('[Detector] Error during polling cycle:', err);
    } finally {
      this.isPolling = false;
    }
  }

  public static startContinuousPolling(): void {
    const cfg = ConfigManager.get();
    console.log(`[Detector] Starting continuous issue polling every ${cfg.pollIntervalSeconds} seconds...`);

    // Initial check
    this.pollOnce();

    setInterval(() => {
      this.pollOnce();
    }, cfg.pollIntervalSeconds * 1000);
  }
}
