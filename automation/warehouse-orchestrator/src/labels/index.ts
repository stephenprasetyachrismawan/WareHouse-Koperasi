import { GitHubClient } from '../github';
import { Database } from '../database';

export class LabelStateMachine {
  public static readonly ALL_WORKFLOW_LABELS = [
    'agent:run',
    'agent:planning',
    'agent:coding',
    'agent:testing',
    'agent:dev-check',
    'agent:pr',
    'agent:merging',
    'agent:done',
    'agent:blocked',
    'agent:failed',
    'agent:retry',
    'agent:identity-authorization-required',
    'agent:post-merge-failed'
  ];

  /**
   * GitHub comment bodies cap out at 65536 chars; a raw agent-output dump
   * (job #11, issue #10) blew past that and made the comment call itself
   * fail with "Validation Failed", masking the real error. Every message
   * that reaches this class funnels through here, so this is the one place
   * that needs to guarantee a safe size — full detail belongs in on-disk
   * logs (see ClaudeCodeRunner), not in the job_events row or the comment.
   */
  private static readonly MAX_MESSAGE_LENGTH = 4000;

  private static truncateMessage(message: string): string {
    if (message.length <= this.MAX_MESSAGE_LENGTH) return message;
    const omitted = message.length - this.MAX_MESSAGE_LENGTH;
    return `${message.slice(0, this.MAX_MESSAGE_LENGTH)}\n\n...[truncated ${omitted} more characters — see .agent-runtime/logs in the issue worktree for full output]`;
  }

  public static async transition(
    jobId: number,
    issueNumber: number,
    nextLabel: string,
    message: string
  ): Promise<void> {
    const safeMessage = this.truncateMessage(message);
    const toRemove = this.ALL_WORKFLOW_LABELS.filter((l) => l !== nextLabel);

    await GitHubClient.updateIssueLabels(issueNumber, nextLabel, toRemove);
    await Database.updateJobState(jobId, nextLabel);
    await Database.addJobEvent(jobId, nextLabel, safeMessage);

    const commentBody = `Checkpoint: **${nextLabel}**\n\n${safeMessage}`;
    await GitHubClient.postIssueComment(issueNumber, commentBody);
  }
}
