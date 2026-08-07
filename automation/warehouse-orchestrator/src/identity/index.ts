import fs from 'fs';
import path from 'path';
import { Database } from '../database';

export interface ContributorMember {
  githubId: number;
  gitName: string;
  gitEmail: string;
  minimumPermission: string;
  canTrigger: boolean;
  canReceiveAttribution: boolean;
}

export interface TeamIdentities {
  schemaVersion: number;
  repository: string;
  repositoryOwner: string;
  members: Record<string, ContributorMember>;
}

const REGISTRY_PATH = path.resolve(__dirname, '../../config/team-identities.json');

export class IdentityManager {
  private static registry: TeamIdentities;

  public static getRegistry(): TeamIdentities {
    if (!this.registry) {
      const raw = fs.readFileSync(REGISTRY_PATH, 'utf-8');
      this.registry = JSON.parse(raw);
    }
    return this.registry;
  }

  public static isRegisteredContributor(username: string): boolean {
    const reg = this.getRegistry();
    return Boolean(reg.members[username]);
  }

  public static getMember(username: string): ContributorMember | null {
    const reg = this.getRegistry();
    return reg.members[username] || null;
  }

  public static resolveEffectiveActor(
    issueAuthor: string,
    triggerActor: string,
    authorPermission: string = 'write'
  ): { effectiveActor: string; member: ContributorMember } {
    const reg = this.getRegistry();
    const ownerMember = reg.members[reg.repositoryOwner];

    if (this.isRegisteredContributor(issueAuthor)) {
      const hasWrite = ['write', 'maintain', 'admin'].includes(authorPermission.toLowerCase());
      if (hasWrite) {
        return {
          effectiveActor: issueAuthor,
          member: reg.members[issueAuthor]
        };
      }
    }

    // External author or registered without write access -> fallback to repository owner
    return {
      effectiveActor: reg.repositoryOwner,
      member: ownerMember
    };
  }

  public static async hasValidToken(username: string): Promise<boolean> {
    const tokenRecord = await Database.getToken(username);
    if (!tokenRecord || !tokenRecord.access_token) {
      return false;
    }
    if (tokenRecord.expires_at && tokenRecord.expires_at < Date.now() / 1000) {
      return false; // Token expired
    }
    return true;
  }
}
