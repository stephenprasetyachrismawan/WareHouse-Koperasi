# CI Security and Regression Gates

The repository CI workflow is a required verification lane. It runs on pull requests, pushes to `main`, and manual dispatches.

## Required gates

- lockfile-based Composer and npm installation;
- `vendor/bin/pint --test`;
- PHPStan/Larastan through `composer test`;
- Laravel tests, including security regression tests;
- `npm run build`;
- `composer audit`;
- `npm audit --audit-level=high`;
- Gitleaks secret scan using `.gitleaks.toml`.

PHPStan failures remain visible. The workflow does not add a broad `ignoreErrors` rule or convert an unavailable scanner into a pass.

## Secret scan review

The historical scanner result at `.claude/skills/laravel-best-practices/rules/config.md` line 30 was a documentation example, not a credential. The example now uses `<external-secret-reference>`. The exact historical commit/path is allowlisted in `.gitleaks.toml` with this decision recorded here; broad path or rule suppressions are not used.

Local verification:

```bash
scripts/verification/secret-scan.sh .
```

If Gitleaks is not installed, the script exits with `2` and reports `NOT VERIFIED`.
