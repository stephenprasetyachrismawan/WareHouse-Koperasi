import { IdentityManager } from '../identity';
import { Database } from '../database';
import { Octokit } from '@octokit/rest';
import readline from 'readline';

async function promptToken(username: string): Promise<string> {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
  });

  return new Promise((resolve) => {
    rl.question(`Enter GitHub User Access Token for @${username}: `, (answer) => {
      rl.close();
      resolve(answer.trim());
    });
  });
}

function parseTokenArg(): string {
  for (let i = 0; i < process.argv.length; i++) {
    if (process.argv[i] === '--token' && process.argv[i + 1]) {
      return process.argv[i + 1];
    }
  }
  return '';
}

async function main() {
  const cmd = process.argv[2];
  const targetUser = process.argv[3];

  if (cmd === 'user') {
    if (!targetUser) {
      console.error('Usage: npm run auth:user -- <github-username> [--token <token>]');
      process.exit(1);
    }

    if (!IdentityManager.isRegisteredContributor(targetUser)) {
      console.error(`Error: User @${targetUser} is not listed in team-identities.json!`);
      process.exit(1);
    }

    console.log(`=== GitHub User Authorization for @${targetUser} ===`);
    let token = parseTokenArg() || process.env.GITHUB_TOKEN || '';

    if (!token && process.stdin.isTTY) {
      token = await promptToken(targetUser);
    }

    if (!token) {
      console.error('No token provided.');
      process.exit(1);
    }

    try {
      const octokit = new Octokit({ auth: token });
      const userRes = await octokit.users.getAuthenticated();
      console.log(`Verified authenticated GitHub user: @${userRes.data.login} (${userRes.data.name || ''})`);

      if (userRes.data.login.toLowerCase() !== targetUser.toLowerCase()) {
        console.error(`Mismatch! Token belongs to @${userRes.data.login}, but target is @${targetUser}`);
        process.exit(1);
      }

      await Database.saveToken(targetUser, token);
      console.log(`Authorization saved successfully for @${targetUser}.`);
    } catch (e: any) {
      console.error(`Authorization failed: ${e.message}`);
      process.exit(1);
    }
  } else if (cmd === 'status') {
    console.log('=== Contributor Authorization Status ===');
    const reg = IdentityManager.getRegistry();
    for (const [username, member] of Object.entries(reg.members)) {
      const hasAuth = await IdentityManager.hasValidToken(username);
      console.log(`- @${username} (${member.gitName} <${member.gitEmail}>): ${hasAuth ? 'AUTHORIZED' : 'NOT AUTHORIZED'}`);
    }
  } else {
    console.log('Usage: npm run auth:user -- <username> [--token <token>] | npm run auth:status');
  }
}

main();
