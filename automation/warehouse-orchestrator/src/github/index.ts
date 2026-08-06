import { Octokit } from '@octokit/rest';
import { Database } from '../database';
import { ConfigManager } from '../config';

export class GitHubClient {
  public static async getOctokitForUser(username: string): Promise<Octokit> {
    const tokenRecord = await Database.getToken(username);
    const token = tokenRecord?.access_token || process.env.GITHUB_TOKEN;

    if (!token) {
      throw new Error(`No valid GitHub authorization token found for user ${username}`);
    }

    return new Octokit({ auth: token });
  }

  public static async getSystemOctokit(): Promise<Octokit> {
    const cfg = ConfigManager.get();
    const owner = cfg.githubRepository.split('/')[0];
    // Fallback to user token for repo owner or environment token
    const tokenRecord = await Database.getToken(owner);
    const token = tokenRecord?.access_token || process.env.GITHUB_TOKEN;

    return new Octokit({ auth: token });
  }

  public static async fetchOpenIssuesWithLabel(labelName: string = 'agent:run'): Promise<any[]> {
    const octokit = await this.getSystemOctokit();
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');

    const response = await octokit.issues.listForRepo({
      owner,
      repo,
      state: 'open',
      labels: labelName,
      sort: 'created',
      direction: 'asc'
    });

    // Filter out PRs
    return response.data.filter((item) => !item.pull_request);
  }

  public static async updateIssueLabels(issueNumber: number, addLabel: string, removeLabels: string[] = []): Promise<void> {
    const octokit = await this.getSystemOctokit();
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');

    for (const rLabel of removeLabels) {
      try {
        await octokit.issues.removeLabel({ owner, repo, issue_number: issueNumber, name: rLabel });
      } catch (err) {
        // Ignore label removal errors if not present
      }
    }

    await octokit.issues.addLabels({
      owner,
      repo,
      issue_number: issueNumber,
      labels: [addLabel]
    });
  }

  public static async postIssueComment(issueNumber: number, commentBody: string): Promise<void> {
    const octokit = await this.getSystemOctokit();
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');

    await octokit.issues.createComment({
      owner,
      repo,
      issue_number: issueNumber,
      body: commentBody
    });
  }

  public static async createPullRequest(
    effectiveActor: string,
    title: string,
    body: string,
    headBranch: string,
    baseBranch: string = 'main'
  ): Promise<{ prNumber: number; htmlUrl: string }> {
    const octokit = await this.getOctokitForUser(effectiveActor);
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');

    const response = await octokit.pulls.create({
      owner,
      repo,
      title,
      body,
      head: headBranch,
      base: baseBranch
    });

    return {
      prNumber: response.data.number,
      htmlUrl: response.data.html_url
    };
  }

  public static async squashMergePullRequest(
    effectiveActor: string,
    prNumber: number,
    commitTitle: string
  ): Promise<{ merged: boolean; sha: string }> {
    const octokit = await this.getOctokitForUser(effectiveActor);
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');

    const response = await octokit.pulls.merge({
      owner,
      repo,
      pull_number: prNumber,
      commit_title: commitTitle,
      merge_method: 'squash'
    });

    return {
      merged: response.data.merged,
      sha: response.data.sha
    };
  }

  public static async provisionLabels(): Promise<void> {
    const octokit = await this.getSystemOctokit();
    const cfg = ConfigManager.get();
    const [owner, repo] = cfg.githubRepository.split('/');
    const labelsConfig = JSON.parse(
      require('fs').readFileSync(require('path').resolve(__dirname, '../../config/labels.json'), 'utf-8')
    );

    for (const [name, meta] of Object.entries(labelsConfig.labels) as [string, any][]) {
      try {
        await octokit.issues.getLabel({ owner, repo, name });
        await octokit.issues.updateLabel({ owner, repo, name, color: meta.color, description: meta.description });
      } catch (e) {
        await octokit.issues.createLabel({ owner, repo, name, color: meta.color, description: meta.description });
      }
    }
  }
}
