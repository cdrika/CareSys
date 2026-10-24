# Development and operation

CareSys uses MySQL 8.4.13 at 127.0.0.1:3307, independently of Caltrans's
MariaDB. Dependencies are locked in composer.lock. Installed XAMPP PHP 8.2 is
compatible; use a maintained PHP 8.4 patch release for the production target.

## Local commands

```powershell
php composer.phar install
php spark caresys:migrate
php spark db:seed DemoSeeder
php spark caresys:jobs:provision-local
php spark serve --host 127.0.0.1 --port 8080
```

Open http://localhost:8080/login. Account details are in docs/demo-accounts.md.
The local server serves public/ only. Apache's repository .htaccess blocks other
paths when mod_rewrite is enabled. Production must point its document root
directly at public/ and verify that .env, source, vendor, writable and the Word
specifications cannot be downloaded.

`caresys:migrate` uses the dedicated schema account for both migration history
and package migrations. The web account has no DDL or global database privileges.
Tests use caresys_test. The .env.example contains placeholders; existing generated
local credentials are private in .env. Never replace that file with the example.

## Jobs

```powershell
php spark caresys:jobs:work
```

Schedule this bounded command every minute with Windows Task Scheduler locally
or a restricted systemd timer in deployment. No OS task has been installed.
Capture failures and alert an operator. The command expires grants and invokes
the normal activation service for fully authorized, due joining requests.
Repeated runs cannot create duplicate employment episodes.

Local synthetic agencies have limited dedicated worker principals with random
credentials. Human demo passwords do not authenticate them. Other agencies await
reviewed worker provisioning. No worker impersonates a candidate or manufactures
an agreement. Local provisioning is restricted to development/synthetic agencies.

Provider work remains durable in cs_outbox with pending_provider state. It is
not marked sent, paid, signed or checked without an actual configured provider.

## Verification

```powershell
php vendor/bin/phpunit --no-coverage
```

Browser tests require Node, Playwright and installed Microsoft Edge:

```powershell
$env:CARESYS_NODE_MODULES = 'PATH_TO_NODE_MODULES'
node tests/Browser/smoke.cjs
node tests/Browser/final-check.cjs
```

The full sweep checks permitted pages for all twelve demo roles. Focused checks
cover database-session login/sign-out and changed pages. Tests use MySQL, not an
in-memory substitute. The asset race starts two independent PHP/MySQL processes.
The runtime database is never reset or seeded by PHPUnit.

## Backup and restore

Before migrating business data, back up the database, private objects and required
keys, recording release and schema versions. Use a dedicated backup login path
configured interactively with mysql_config_editor, not passwords on command lines.
Use mysqldump --single-transaction for InnoDB logical backups. Protect all exports.

Restore into an isolated environment and verify login, tenant separation, grants,
contracts, invoice quantities and worker retries. No recovery-time claim has been
measured. Never use Caltrans's data directory or database configuration for CareSys.

MySQL DDL may implicitly commit. Destructive down migrations are intentionally
disabled; use reviewed forward migrations or a tested, consistent restore.

## Production dependencies

Use a separate production database without demo seeding or shared passwords.
Configure HTTPS, secure cookies, secrets, email/verification, privileged MFA,
approved signing and real provider adapters. Approve jurisdiction, retention and
screening rules. Establish monitoring, backups and restoration exercises.
See docs/implementation-status.md for remaining scope; local tests do not certify
production security, compliance, accessibility or performance.
