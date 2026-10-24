<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\MigrationRunner;

class MigrateDatabase extends BaseCommand
{
    protected $group = 'CareSys';
    protected $name = 'caresys:migrate';
    protected $description = 'Run migrations using the dedicated schema account, keeping web credentials restricted.';

    public function run(array $params)
    {
        $config = config(\Config\Database::class);
        if ($config->migrations['username'] === '' || $config->migrations['database'] !== 'caresys') {
            CLI::error('Configure the dedicated caresys migration account first.');
            return EXIT_ERROR;
        }
        // Both migration history and package migration objects resolve the default group.
        // This change exists only for this CLI process, never for web requests.
        $config->default = $config->migrations;
        $config->defaultGroup = 'default';
        $runner = new MigrationRunner(config(\Config\Migrations::class));
        $runner->setNamespace(null);
        if (!$runner->latest()) {
            CLI::error('Migrations failed.');
            return EXIT_ERROR;
        }
        foreach ($runner->getCliMessages() as $message) {
            CLI::write($message);
        }
        CLI::write('CareSys migrations complete.', 'green');
        return EXIT_SUCCESS;
    }
}
