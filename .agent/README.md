# .agent

This directory contains repository-owned instructions for human developers and AI coding agents.

Start with the root `AGENTS.md`, then read `.agent/WORKFLOW.md`. The product and security documents remain the source of truth.

Expected repository setup:

```bash
npx skills@latest add mattpocock/skills
composer require laravel/boost --dev
php artisan boost:install
```

The external installer may create agent-specific files or a `.agents` directory. Keep this `.agent` directory as the stable project-owned entry point. Review all generated changes before committing them.
