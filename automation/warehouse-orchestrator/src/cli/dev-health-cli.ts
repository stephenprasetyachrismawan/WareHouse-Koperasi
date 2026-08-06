import { DevSessionManager } from '../dev-session';

const health = DevSessionManager.getHealth();
console.log('=== Composer Development Supervisor Health ===');
if (health) {
  console.log(JSON.stringify(health, null, 2));
} else {
  console.log('Health status file (/srv/warehouse-koperasi/state/dev-health.json) not found.');
}
