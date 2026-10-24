<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckConnection extends BaseCommand
{
    protected $group = 'CareSys';
    protected $name = 'caresys:database:check';
    protected $description = 'Check local database authentication without revealing credentials.';

    public function run(array $params)
    {
        $config = config(\Config\Database::class)->default;
        if ($config['hostname'] !== '127.0.0.1' || (int) $config['port'] !== 3307) {
            CLI::error('This diagnostic is limited to 127.0.0.1:3307.');
            return EXIT_ERROR;
        }
        $lines = file(ROOTPATH . '.env', FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^database\.default\.password\s*=\s*(.*)$/', $line, $match)) {
                $literal = trim($match[1]);
                if (strlen($literal) >= 2 && in_array($literal[0], ["'", '"'], true) && $literal[0] === substr($literal, -1)) {
                    $literal = substr($literal, 1, -1);
                }
                CLI::write('Configured password is nonempty: ' . ($config['password'] !== '' ? 'yes' : 'no'));
                CLI::write('Password matches literal file value: ' . (hash_equals($literal, (string) $config['password']) ? 'yes' : 'no (quoting, escaping or variable expansion changed it)'));
            }
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        foreach ([false, true] as $tls) {
            $connection = mysqli_init();
            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            try {
                // Loopback diagnostic only; emulate Workbench's encrypted local connection.
                $flags = 0;
                if ($tls) {
                    $connection->ssl_set(null, null, null, null, null);
                    $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
                }
                $connection->real_connect('127.0.0.1', $config['username'], $config['password'], null, 3307, null, $flags);
                CLI::write(($tls ? 'TLS' : 'Default') . ' login: successful', 'green');
                $connection->close();
            } catch (\mysqli_sql_exception $error) {
                CLI::write(($tls ? 'TLS' : 'Default') . ' login failed: MySQL error ' . $error->getCode());
            }
        }
    }
}
