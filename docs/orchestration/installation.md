# Orchestration Installation & Setup

## System Requirements
- Linux VPS (Amazon Linux 2023 or RHEL/Ubuntu)
- Node.js v20+ & npm
- PHP 8.4+ & Composer
- Git & tmux
- Google Antigravity CLI (`agy`) v1.1+

## Directory Provisioning
```bash
sudo mkdir -p /srv/warehouse-koperasi
sudo chown -R ec2-user:ec2-user /srv/warehouse-koperasi
mkdir -p /srv/warehouse-koperasi/{control,worktrees,orchestrator,state,logs,credentials}
```

## Control Repository Setup
```bash
git clone /opt/project/WareHouse-Koperasi /srv/warehouse-koperasi/control
composer install --working-dir=/srv/warehouse-koperasi/control --no-interaction
```

## Orchestrator Dependencies
```bash
npm run --prefix automation/warehouse-orchestrator install
```
