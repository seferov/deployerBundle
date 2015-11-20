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
 * Class DeployCommand
 * @package Seferov\DeployerBundle\Command
 * @author Farhad Safarov <http://ferhad.in>
 */
class DeployCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('deployer:push')
            ->setDescription('Deploys the app to remote server')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        if ('Fri' == date('D')) {
            $this->output->writeln('
┓┏┓┏┓┃
┛┗┛┗┛┃＼○／
┓┏┓┏┓┃ /     Friday
┛┗┛┗┛┃ノ)
┓┏┓┏┓┃       Deploys
┛┗┛┗┛┃
┓┏┓┏┓┃
┛┗┛┗┛┃
┓┏┓┏┓┃
┃┃┃┃┃┃
┻┻┻┻┻┻
            ');
            // credits: https://twitter.com/davidwalshblog/status/507920632432975872
        }

        // Download the project
        $this->sshClient->exec(sprintf('rm -rf %s', $this->versionsDir . 'ondeck/'));
        $this->sshClient->exec(sprintf('yes | git clone -b %s %s %s', $this->server['git_branch'], $this->server['git'], $this->versionsDir . 'ondeck'));
        $this->output->writeln('<info>Application downloaded.</info>');

        // Versioner
        $version = $this->versioner->getAppVersion($this->server['git_branch']);
        $currentVersion = $this->versioner->getCurrentVersion();

        $this->output->writeln(sprintf('<comment>Version: %s</comment>', $version));
        if ($version == $currentVersion && !$this->input->getOption('force')) {
            $this->output->writeln('<comment>You already have deployed the latest version.</comment>');
            return;
        }

        $appDir = $this->versionsDir . 'ondeck';

        // Make cache and log folders writable
        $this->sshClient->exec(sprintf('cd %s && mkdir -p app/cache app/logs && chmod 777 -R app/cache app/logs', $appDir));

        // Server parameters
        $this->sshClient->exec(sprintf('cp %s/config/parameters.yml %s/app/config/parameters.yml', $this->server['connection']['path'], $appDir));

        if ($currentVersion && !$this->input->getOption('force')) {
            // Copy previous versions vendor
            $this->sshClient->exec(sprintf('cp -rf %s/vendor %s/', $this->versionsDir . $currentVersion, $appDir));
        }

        // Update composer
        $this->sshClient->exec('composer self-update');

        // Install dependencies - composer install
        $this->sshClient->exec(sprintf('cd %s && yes | composer install --optimize-autoloader', $appDir));

        // Move from ondeck to versioned folder
        $appDir = $this->versionsDir . $version;
        if ($this->input->getOption('force')) {
            $this->sshClient->exec(sprintf('rm -rf %s', $appDir));
        }
        $this->sshClient->exec(sprintf('mv %s %s', $this->versionsDir . 'ondeck', $appDir));

        // Symlink
        $this->sshClient->exec(sprintf('rm -rf %s/web', $this->server['connection']['path']));
        $this->sshClient->exec(sprintf('ln -s %s/web %s', $appDir, $this->server['connection']['path']));
        $this->versioner->setNewVersion($version);

        $this->output->writeln('<comment>Your app successfully deployed!</comment>');
    }
}
