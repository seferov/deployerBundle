<?php

namespace Seferov\DeployerBundle\Command;

use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 * @package Seferov\DeployerBundle\Command
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deployer:install')
            ->addArgument(
                'server_name',
                InputArgument::REQUIRED,
                'To which server do you want to install?'
            )
            ->setDescription('Installs deployer to remote server')
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
            throw new \InvalidArgumentException(sprintf('There is no such server named "%s" in the configuration', $serverName));
        }

        $server = $config['servers'][$serverName];

        $sshClient = $this->getContainer()->get('seferov_deployer.ssh_client');
        $sshClient->connect($server['connection']);
        $sshClient->setOutput($output);

        // Install config
        $parametersFile = $this->getContainer()->getParameter('kernel.root_dir') . '/config/parameters.yml';
        $sshClient->exec(sprintf('mkdir -p %s/config', $server['connection']['path']));
        $sshClient->upload($parametersFile, $server['connection']['path'] . '/config/parameters.yml');

        // Init versions
        $sshClient->exec('mkdir -p %s/versions', $server['connection']['path']);

        // Download and install composer
        $sshClient->exec('php -r "readfile(\'https://getcomposer.org/installer\');" | php && mv composer.phar /usr/local/bin/composer');

        $output->writeln('<comment>Deployer successfully installed on your server.</comment>');
    }
}