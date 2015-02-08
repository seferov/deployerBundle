<?php

namespace Seferov\DeployerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Exception\InvalidArgumentException;
use Seferov\DeployerBundle\Deployer\Versioner;

/**
 * Class DeployCommand
 * @package Seferov\DeployerBundle\Command
 */
class DeployCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deployer:push')
            ->addArgument(
                'server_name',
                InputArgument::REQUIRED,
                'To which server do you want to deploy?'
            )
            ->setDescription('Deploys the app to remote server')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get configuration
        $serverName = $input->getArgument('server_name');
        $config = $this->getContainer()->getParameter('seferov_deployer_config');

        if (!array_key_exists($serverName, $config['servers'])) {
            throw new InvalidArgumentException(sprintf('There is no such server named "%s" in the configuration', $serverName));
        }

        $server = $config['servers'][$serverName];
        $versionsDir = $server['connection']['path'].'/versions/';

        $sshClient = $this->getContainer()->get('seferov_deployer.ssh_client');
        $sshClient->connect($server['connection']);
        $sshClient->setOutput($output);

        // Download the project
        $sshClient->exec(sprintf('rm -rf %s', $versionsDir . 'ondeck/'));
        $sshClient->exec(sprintf('git clone %s %s', $server['git'], $versionsDir . 'ondeck'));

        // Versioner
        $versioner = new Versioner($versionsDir, $sshClient);
        $version = $versioner->getAppVersion();
        $currentVersion = $versioner->getCurrentVersion();

        $output->writeln(sprintf('<comment>Version: %s</comment>', $version));
        if ($version == $currentVersion) {
            $output->writeln('<comment>You already have deployed the latest version.</comment>');
            return;
        }

        $appDir = $versionsDir . 'ondeck';

        // Make cache and log folders writable
        $sshClient->exec(sprintf('cd %s && chmod 777 -R app/cache app/logs', $appDir));

        // Server parameters
        $sshClient->exec(sprintf('cp %s/config/parameters.yml %s/app/config/parameters.yml', $server['connection']['path'], $appDir));

        if ($currentVersion) {
            // Copy previous versions vendor
            $sshClient->exec(sprintf('cp -rf %s/vendor %s/', $versionsDir . $currentVersion, $appDir));
        }

        // Update composer
        $sshClient->exec('composer self-update');

        // Install dependencies - composer install
        $sshClient->exec(sprintf('cd %s && yes | composer install --optimize-autoloader', $appDir));

        // Post-deployment tasks
//        $sshClient->exec(sprintf('php %s/app/console cache:clear --env=prod --no-debug', $appDir));
//        $sshClient->exec(sprintf('cd %s && app/console assetic:dump --env=prod --no-debug', $appDir));

        // Move from ondeck to versioned folder
        $appDir = $versionsDir . $version;
        $sshClient->exec(sprintf('mv %s %s', $versionsDir . 'ondeck', $appDir));

        // Symlink
        $sshClient->exec(sprintf('rm %s/web', $server['connection']['path']));
        $sshClient->exec(sprintf('ln -s %s/web %s', $appDir, $server['connection']['path']));
        $versioner->setNewVersion($version);
    }
}