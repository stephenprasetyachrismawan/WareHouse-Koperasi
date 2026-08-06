import { Database } from '../database';

async function main() {
  const cmd = process.argv[2];
  const targetId = Number(process.argv[3]);

  const db = Database.getDb();

  if (cmd === 'list') {
    db.all(`SELECT id, issue_number, effective_actor, state, agent, model, created_at FROM jobs ORDER BY id DESC LIMIT 20`, (err, rows) => {
      if (err) throw err;
      console.log('=== Orchestrator Jobs ===');
      console.table(rows);
    });
  } else if (cmd === 'show') {
    if (!targetId) {
      console.error('Usage: npm run jobs:show -- <job-id-or-issue-number>');
      process.exit(1);
    }
    db.get(`SELECT * FROM jobs WHERE id = ? OR issue_number = ?`, [targetId, targetId], (err, row) => {
      if (err) throw err;
      console.log('=== Job Record ===');
      console.log(JSON.stringify(row, null, 2));
    });
  } else if (cmd === 'retry') {
    if (!targetId) {
      console.error('Usage: npm run jobs:retry -- <issue-number>');
      process.exit(1);
    }
    console.log(`Resetting state for issue #${targetId} to retry...`);
    db.run(`UPDATE jobs SET state = 'agent:retry' WHERE issue_number = ?`, [targetId], (err) => {
      if (err) throw err;
      console.log(`Issue #${targetId} marked for retry.`);
    });
  } else if (cmd === 'cancel') {
    if (!targetId) {
      console.error('Usage: npm run jobs:cancel -- <issue-number>');
      process.exit(1);
    }
    console.log(`Cancelling job for issue #${targetId}...`);
    db.run(`UPDATE jobs SET state = 'agent:failed', failure_reason = 'Cancelled by administrator' WHERE issue_number = ?`, [targetId], (err) => {
      if (err) throw err;
      console.log(`Issue #${targetId} job cancelled.`);
    });
  } else {
    console.log('Usage: npm run jobs:list | show | retry | cancel');
  }
}

main();
