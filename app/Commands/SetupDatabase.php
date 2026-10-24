<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class SetupDatabase extends BaseCommand
{
    protected $group = 'CareSys';
    protected $name = 'caresys:database:setup';
    protected $description = 'Provision isolated local MySQL databases and scoped accounts.';

    public function run(array $params)
    {
        $config = config(Database::class)->default;
        if (ENVIRONMENT !== 'development' || $config['username'] !== 'root'
            || $config['hostname'] !== '127.0.0.1' || (int) $config['port'] !== 3307) {
            CLI::error('Setup requires development and the temporary root connection on 127.0.0.1:3307.');
            return EXIT_ERROR;
        }
        $config['database'] = '';
        $config['DBDebug'] = false;
        $stage = 'connect';
        try {
            $admin = Database::connect($config, false);
            $version = $admin->query('SELECT VERSION() AS version')->getRow()->version;
            if (!preg_match('/^8\.4\./', $version) || stripos($version, 'MariaDB') !== false) {
                throw new \RuntimeException('Expected MySQL 8.4.');
            }
            $stage = 'check existing accounts';
            $accounts = ['caresys_app', 'caresys_migrate', 'caresys_test'];
            foreach ($accounts as $account) {
                if ($admin->query('SELECT User FROM mysql.user WHERE User = ?', [$account])->getNumRows() > 0) {
                    CLI::error('A CareSys account already exists. No existing credentials were changed; review setup state.');
                    return EXIT_ERROR;
                }
            }
            $stage = 'create databases';
            foreach (['caresys', 'caresys_test'] as $schema) {
                $admin->query("CREATE DATABASE IF NOT EXISTS `{$schema}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            $stage = 'create scoped accounts';
            $passwords = [];
            foreach ($accounts as $account) {
                $passwords[$account] = bin2hex(random_bytes(32));
                foreach (['localhost', '127.0.0.1'] as $host) {
                    $admin->query("CREATE USER '{$account}'@'{$host}' IDENTIFIED BY '" . $passwords[$account] . "'");
                    $privileges = $account === 'caresys_app' ? 'SELECT, INSERT, UPDATE, DELETE' : 'ALL PRIVILEGES';
                    $schema = $account === 'caresys_test' ? 'caresys_test' : 'caresys';
                    $admin->query("GRANT {$privileges} ON `{$schema}`.* TO '{$account}'@'{$host}'");
                }
            }
            $stage = 'save local environment';
            $path = ROOTPATH . '.env';
            $contents = file_get_contents($path);
            foreach (['username' => 'caresys_app', 'password' => $passwords['caresys_app']] as $key => $value) {
                $contents = preg_replace('/^database\.default\.' . $key . '\s*=.*$/m', "database.default.{$key} = '{$value}'", $contents);
            }
            foreach (['migrations' => 'caresys_migrate', 'tests' => 'caresys_test'] as $group => $account) {
                $schema = $group === 'tests' ? 'caresys_test' : 'caresys';
                $contents .= "\n# Scoped local {$group} account\n";
                foreach (['hostname' => '127.0.0.1', 'port' => '3307', 'database' => $schema, 'username' => $account, 'password' => $passwords[$account], 'DBDriver' => 'MySQLi'] as $key => $value) {
                    $contents .= "database.{$group}.{$key} = '{$value}'\n";
                }
            }
            if (file_put_contents($path, $contents, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to save credentials.');
            }
            $admin->close();
            CLI::write('MySQL ' . $version . ': caresys and caresys_test are ready.', 'green');
            CLI::write('Scoped credentials saved locally. Root password removed from .env.');
            return EXIT_SUCCESS;
        } catch (\Throwable $error) {
            CLI::error('Provisioning failed at ' . $stage . ' (' . get_class($error) . ', code ' . $error->getCode() . '). Passwords are never displayed.');
            $message = $error->getMessage();
            if (stripos($message, 'Access denied') !== false) {
                CLI::error('MySQL rejected the root login. Check the password in .env; it must match the password set in MySQL Configurator.');
            } elseif (stripos($message, 'actively refused') !== false || stripos($message, 'Connection refused') !== false) {
                CLI::error('MySQL is not accepting connections on port 3307.');
            }
            return EXIT_ERROR;
        }
    }
}
