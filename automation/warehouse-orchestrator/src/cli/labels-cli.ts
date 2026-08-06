import { GitHubClient } from '../github';

async function main() {
  const cmd = process.argv[2];
  if (cmd === 'provision') {
    console.log('Provisioning orchestrator GitHub labels...');
    try {
      await GitHubClient.provisionLabels();
      console.log('Labels provisioned successfully.');
    } catch (e: any) {
      console.error('Error provisioning labels:', e.message);
      process.exit(1);
    }
  } else {
    console.log('Usage: npm run labels:provision');
  }
}

main();
