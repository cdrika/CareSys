# CareSys local setup

The application uses CodeIgniter 4.7.5 and Shield 1.4.1 with MySQL 8.4 on
127.0.0.1:3307. Caltrans's XAMPP MariaDB configuration is separate.

## Current state

The framework, authentication and principal business workflows are installed.
Browser CSRF protection uses MySQL-backed sessions, security headers are enabled,
and automatic routing is disabled. See implementation-status.md for the working
modules and remaining scope, demo-accounts.md for access, and
runbooks/development.md for the current operator instructions. Mail delivery,
verification, privileged MFA and provider integrations remain production dependencies.

## Credentials

The ignored local `.env` holds generated development credentials. Never share it
or include it in screenshots. The root password is no longer stored there.

| Connection | Account | Access |
| --- | --- | --- |
| default | caresys_app | SELECT, INSERT, UPDATE, DELETE in caresys |
| migrations | caresys_migrate | Schema management in caresys only |
| tests | caresys_test | Schema management in caresys_test only |

Provisioning used `php spark caresys:database:setup` with a temporary local root
connection. It refuses to overwrite existing accounts. MySQL DDL is not transactional;
review partial setup manually if provisioning is interrupted. Keep the migration
and test credentials out of any future production web deployment.

## Commands

Run from the CareSys project directory:

```powershell
php composer.phar install
php spark caresys:migrate
php vendor/bin/phpunit --no-coverage
php spark serve --host 127.0.0.1 --port 8080
```

Browse http://localhost:8080/login to inspect the installed authentication screen.
Use `caresys:migrate` rather than ordinary `spark migrate`: the wrapper gives
both migration history and package migrations the dedicated schema connection
without granting DDL to the application's database user. Migrations may implicitly
commit; take and verify backups before future migrations containing business data.

Tests use a separate real MySQL database. The application connection is checked
without modifying its business data. Tests assert that the runtime account has
no DDL or global privileges and the test account has no access to caresys.

XAMPP currently reports a missing Xdebug DLL at PHP startup. This does not stop
execution; coverage remains disabled until a compatible coverage driver is installed.

Only `public/` should be served by a web server. Do not expose the repository root,
`.env`, documents, vendor files or writable storage through an Apache alias.
