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

  public static async transition(
    jobId: number,
    issueNumber: number,
    nextLabel: string,
    message: string
  ): Promise<void> {
    const toRemove = this.ALL_WORKFLOW_LABELS.filter((l) => l !== nextLabel);
    
    await GitHubClient.updateIssueLabels(issueNumber, nextLabel, toRemove);
    await Database.updateJobState(jobId, nextLabel);
    await Database.addJobEvent(jobId, nextLabel, message);

    const commentBody = `Checkpoint: **${nextLabel}**\n\n${message}`;
    await GitHubClient.postIssueComment(issueNumber, commentBody);
  }
}
