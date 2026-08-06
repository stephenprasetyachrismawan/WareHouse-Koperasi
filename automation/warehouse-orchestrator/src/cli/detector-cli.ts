import { execSync } from 'child_process';
import fs from 'fs';

const cmd = process.argv[2];

if (cmd === 'status') {
  try {
    const res = execSync('tmux has-session -t warehouse-issue-detector 2>&1', { encoding: 'utf-8' });
    console.log('Detector session status: RUNNING');
  } catch (e) {
    console.log('Detector session status: STOPPED');
  }
} else if (cmd === 'logs') {
  const logFile = '/srv/warehouse-koperasi/logs/detector/detector.log';
  if (fs.existsSync(logFile)) {
    const content = execSync(`tail -n 50 "${logFile}"`, { encoding: 'utf-8' });
    console.log(content);
  } else {
    console.log('No detector log file found.');
  }
} else {
  console.log('Usage: npm run detector:status | npm run detector:logs');
}
