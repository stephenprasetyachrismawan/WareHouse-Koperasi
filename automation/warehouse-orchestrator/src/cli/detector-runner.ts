import { DetectorService } from '../detector';

console.log('[DetectorRunner] Starting Issue Detector Loop...');
DetectorService.startContinuousPolling();
