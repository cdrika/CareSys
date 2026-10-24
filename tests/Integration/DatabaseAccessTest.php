<?php

use CodeIgniter\Test\CIUnitTestCase;

final class DatabaseAccessTest extends CIUnitTestCase
{
    public function testApplicationUsesItsOwnSchemaWithoutGlobalOrDdlPrivileges(): void
    {
        $connection = \Config\Database::connect('default', false);
        $identity = $connection->query('SELECT DATABASE() AS schema_name, CURRENT_USER() AS principal')->getRowArray();
        $this->assertSame('caresys', $identity['schema_name']);
        $this->assertStringStartsWith('caresys_app@', $identity['principal']);
        $grants = implode("\n", array_map(static fn ($row) => implode(' ', $row), $connection->query('SHOW GRANTS')->getResultArray()));
        $this->assertStringContainsString('SELECT, INSERT, UPDATE, DELETE ON `caresys`.*', $grants);
        $this->assertStringNotContainsString('ALL PRIVILEGES', $grants);
        $this->assertStringNotContainsString('GRANT OPTION', $grants);
        $this->assertStringNotContainsString('CREATE', $grants);
        $this->assertStringNotContainsString('`caresys_test`', $grants);
        $this->assertTrue($connection->tableExists('users'));
        $this->assertTrue($connection->tableExists('auth_identities'));
        $connection->close();
    }

    public function testTestConnectionIsIsolatedOnActualMysql(): void
    {
        $connection = \Config\Database::connect('tests', false);
        $identity = $connection->query('SELECT DATABASE() AS schema_name, CURRENT_USER() AS principal, VERSION() AS version')->getRowArray();
        $this->assertSame('caresys_test', $identity['schema_name']);
        $this->assertStringStartsWith('caresys_test@', $identity['principal']);
        $this->assertStringStartsWith('8.4.', $identity['version']);
        $grants = implode("\n", array_map(static fn ($row) => implode(' ', $row), $connection->query('SHOW GRANTS')->getResultArray()));
        $this->assertStringContainsString('ON `caresys_test`.*', $grants);
        $this->assertStringNotContainsString('ON `caresys`.*', $grants);
        $connection->close();
    }
}
