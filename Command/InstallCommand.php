<?php

/*
 * This file is part of the SeferovDeployerBundle package.
 *
 * (c) Farhad Safarov <http://ferhad.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\DeployerBundle\Command;

/**
 * Class InstallCommand
 * @package Seferov\DeployerBundle\Command
 * @author Farhad Safarov <http://ferhad.in>
 */
class InstallCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('deployer:install')
            ->setDescription('Installs deployer to remote server')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        // Install config
        $parametersFile = $this->getContainer()->getParameter('kernel.root_dir') . '/config/parameters.yml';
        $this->sshClient->exec(sprintf('mkdir -p %s/config', $this->server['connection']['path']));
        $this->sshClient->upload($parametersFile, $this->server['connection']['path'] . '/config/parameters.yml');

        // Init versions
        $this->sshClient->exec(sprintf('mkdir -p %s/versions', $this->server['connection']['path']));
        $this->sshClient->exec(sprintf('touch %s/versions/versions.txt', $this->server['connection']['path']));

        // Download and install composer
        $this->sshClient->exec('php -r "readfile(\'https://getcomposer.org/installer\');" | php && mv composer.phar /usr/local/bin/composer');

        $this->output->writeln('<comment>Deployer successfully installed on your server.</comment>');
    }
}