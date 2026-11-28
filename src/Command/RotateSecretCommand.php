<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Command;

use Doctrine\DBAL\Connection;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Re-encrypt the connector params after a change of APP_SECRET.
 * The params are encrypted with the last 16 characters of the secret: once APP_SECRET is changed,
 * the values saved with the old secret can't be decrypted anymore ("The MAC is invalid").
 *
 * Two ways to use it:
 *  - the new APP_SECRET is already set in .env.local:
 *      php bin/console myddleware:rotate-secret --old-secret=<previous APP_SECRET> [--dry-run]
 *  - let the command generate a new secret, re-encrypt the params and write it in .env.local:
 *      php bin/console myddleware:rotate-secret --generate [--dry-run]
 */
class RotateSecretCommand extends Command
{
    private Connection $connection;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(Connection $connection, ParameterBagInterface $params, LoggerInterface $logger, ?string $name = null)
    {
        parent::__construct($name);
        $this->connection = $connection;
        $this->params = $params;
        $this->logger = $logger;
        $this->projectDir = (string) $params->get('kernel.project_dir');
    }

    protected function configure()
    {
        $this
            ->setName('myddleware:rotate-secret')
            ->setDescription('Re-encrypt the connector parameters with a new APP_SECRET')
            ->addOption('old-secret', null, InputOption::VALUE_REQUIRED, 'The previous APP_SECRET (used to encrypt the existing connector parameters). The current APP_SECRET is the new one.')
            ->addOption('generate', 'g', InputOption::VALUE_NONE, 'Generate a new APP_SECRET, re-encrypt the connector parameters with it and write it in .env.local (the current APP_SECRET is the old one)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be re-encrypted without saving anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $generate = (bool) $input->getOption('generate');
        $dryRun = (bool) $input->getOption('dry-run');
        $currentSecret = (string) $this->params->get('secret');

        if ($generate) {
            if (null !== $input->getOption('old-secret')) {
                $output->writeln('<error>The options --generate and --old-secret can\'t be used together: with --generate, the current APP_SECRET is the old secret.</error>');

                return Command::INVALID;
            }
            $error = $this->checkEnvLocalWritable();
            if (null !== $error) {
                $output->writeln('<error>'.$error.'</error>');

                return Command::FAILURE;
            }
            $oldSecret = $currentSecret;
            $newSecret = bin2hex(random_bytes(16));
        } else {
            $oldSecret = (string) $input->getOption('old-secret');
            $newSecret = $currentSecret;
            if ('' === $oldSecret) {
                $output->writeln('<error>Use --old-secret=<previous APP_SECRET>, or --generate to create a new secret.</error>');

                return Command::INVALID;
            }
        }

        if (strlen($oldSecret) < 16 || strlen($newSecret) < 16) {
            $output->writeln('<error>Both secrets must contain at least 16 characters.</error>');

            return Command::INVALID;
        }

        $oldKey = substr($oldSecret, -16);
        $newKey = substr($newSecret, -16);
        if ($oldKey === $newKey) {
            $output->writeln('<info>The old and the new secret share the same encryption key (last 16 characters): nothing to do.</info>');

            return Command::SUCCESS;
        }

        $oldEncrypter = new Encrypter($oldKey);
        $newEncrypter = new Encrypter($newKey);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT cp.id, cp.name, cp.value, cp.conn_id, c.name connector_name
               FROM connectorparam cp
               LEFT JOIN connector c ON c.id = cp.conn_id'
        );

        $updates = [];
        $alreadyOk = 0;
        $failed = [];
        foreach ($rows as $row) {
            $value = $row['value'];
            if (!is_string($value) || '' === $value) {
                continue;
            }
            // Already readable with the new secret: nothing to do (the command can be run several times)
            if ($this->canDecrypt($newEncrypter, $value)) {
                ++$alreadyOk;
                continue;
            }
            try {
                $plain = $oldEncrypter->decrypt($value);
            } catch (DecryptException $e) {
                $failed[] = $row;
                continue;
            }
            $updates[$row['id']] = $newEncrypter->encrypt($plain);
        }

        $output->writeln(sprintf('%d parameter(s) to re-encrypt, %d already encrypted with the new secret, %d not readable with either secret.', count($updates), $alreadyOk, count($failed)));
        foreach ($failed as $row) {
            $output->writeln(sprintf('<comment>  - not readable: connector "%s" (id %s), param "%s"</comment>', $row['connector_name'], $row['conn_id'], $row['name']));
        }

        if ($dryRun) {
            $output->writeln('<info>Dry run: nothing saved'.($generate ? ', .env.local not changed' : '').'.</info>');

            return Command::SUCCESS;
        }
        if (empty($updates) && !$generate) {
            return empty($failed) ? Command::SUCCESS : Command::FAILURE;
        }

        $envBackup = null;
        $this->connection->beginTransaction();
        try {
            foreach ($updates as $id => $value) {
                $this->connection->update('connectorparam', ['value' => $value], ['id' => $id]);
            }
            // Write the new secret before the commit: if it fails, the database is rolled back
            // and the params stay readable with the current secret
            if ($generate) {
                $envBackup = $this->writeSecretInEnvLocal($newSecret);
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            // The database has been rolled back: restore the previous .env.local too
            if (null !== $envBackup) {
                copy($envBackup, $this->projectDir.'/.env.local');
            }
            $message = 'Failed to re-encrypt the connector parameters: '.$e->getMessage();
            $output->writeln('<error>'.$message.'</error>');
            $this->logger->error($message);

            return Command::FAILURE;
        }

        $message = count($updates).' connector parameter(s) re-encrypted with the new APP_SECRET.';
        $output->writeln('<info>'.$message.'</info>');
        $this->logger->info($message);
        if ($generate) {
            $output->writeln('<info>New APP_SECRET written in .env.local.</info>');
            $output->writeln(sprintf('<comment>The previous .env.local has been saved in %s: delete it once everything works (it contains the old secret).</comment>', basename($envBackup)));
            $output->writeln('<comment>Users have to log in again (sessions and "remember me" cookies are signed with APP_SECRET). Restart PHP-FPM / the web server if it caches environment variables.</comment>');
            $this->logger->info('APP_SECRET regenerated by myddleware:rotate-secret');
        }

        return empty($failed) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Return an error message if the new secret can't be written in .env.local, null otherwise.
     */
    private function checkEnvLocalWritable(): ?string
    {
        $envLocal = $this->projectDir.'/.env.local';
        // When the env is dumped (composer dump-env), .env.local is ignored by Symfony
        if (file_exists($this->projectDir.'/.env.local.php')) {
            return '.env.local.php exists (composer dump-env): .env.local is ignored. Set the new secret manually and use --old-secret.';
        }
        // APP_SECRET defined as a real environment variable (server, docker...) overrides .env.local
        $dotenvVars = explode(',', $_SERVER['SYMFONY_DOTENV_VARS'] ?? '');
        if (!in_array('APP_SECRET', $dotenvVars, true)) {
            return 'APP_SECRET is defined as a real environment variable (not in a .env file): writing .env.local would have no effect. Change it where it is defined and use --old-secret.';
        }
        if (!file_exists($envLocal) || !is_writable($envLocal)) {
            return '.env.local doesn\'t exist or is not writable.';
        }
        // The secret must come from .env.local, otherwise the file we change isn't the one used
        if (!preg_match('/^[ \t]*APP_SECRET[ \t]*=/m', (string) file_get_contents($envLocal))) {
            return 'APP_SECRET is not defined in .env.local. Add it there first, or set the new secret manually and use --old-secret.';
        }

        return null;
    }

    /**
     * Replace APP_SECRET in .env.local and return the path of the backup of the previous file.
     */
    private function writeSecretInEnvLocal(string $newSecret): string
    {
        $envLocal = $this->projectDir.'/.env.local';
        $content = (string) file_get_contents($envLocal);

        // Backup named .env.*.local so it is ignored by git
        $backup = $this->projectDir.'/.env.secret-backup-'.date('YmdHis').'.local';
        if (false === copy($envLocal, $backup)) {
            throw new \RuntimeException('Unable to back up .env.local');
        }

        $newContent = preg_replace_callback(
            // [^\r\n]* keeps the line ending (CRLF on Windows)
            '/^([ \t]*APP_SECRET[ \t]*=)[^\r\n]*/m',
            fn ($m) => $m[1].$newSecret,
            $content
        );
        if (false === file_put_contents($envLocal, $newContent, LOCK_EX)) {
            throw new \RuntimeException('Unable to write .env.local');
        }

        return $backup;
    }

    private function canDecrypt(Encrypter $encrypter, string $value): bool
    {
        try {
            $encrypter->decrypt($value);

            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
}
