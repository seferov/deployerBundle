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

use Seferov\DeployerBundle\Deployer\WebServer;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

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
        $this->sshClient->exec('export DEBIAN_FRONTEND="noninteractive"');
        $this->sshClient->exec('apt-get update');

        // Before install
        if (array_key_exists('commands', $this->server) && array_key_exists('before_install', $this->server['commands'])) {
            $commands = $this->server['commands']['before_install'];
            foreach ($commands as $command) {
                $this->output->writeln(sprintf('<info>Running %s</info>', $command));
                $this->sshClient->exec(sprintf('yes | %s', $command));
            }
        }

        $this->setParameters();

        // Install required dependencies
        $this->installDependencies();

        // Web Server
        $webServer = new WebServer($this->sshClient, $this->server['connection']['path']);
        $webServer->installApp();

        // Init versions
        $this->sshClient->exec(sprintf('mkdir -p %s/versions', $this->server['connection']['path']));
        $this->sshClient->exec(sprintf('touch %s/versions/versions.txt', $this->server['connection']['path']));

        // Download and install composer
        $this->sshClient->exec('php -r "readfile(\'https://getcomposer.org/installer\');" | php && mv composer.phar /usr/local/bin/composer');

        $this->output->writeln('<comment>Deployer successfully installed on your server.</comment>');
    }

    /**
     * Creates parameters for server
     */
    private function setParameters()
    {
        $this->output->writeln('<info>Please enter parameters for your server. Default values are presented.</info>');

        $yaml = new Yaml();
        $parameters = $yaml->parse(file_get_contents($this->getContainer()->getParameter('kernel.root_dir') . '/config/parameters.yml.dist'));

        $serverParameters = [];
        if (Kernel::VERSION < 2.5) {
            $dialog = $this->getHelper('dialog');
            foreach ($parameters['parameters'] as $key => $value) {
                $serverParameters[$key] = $dialog->ask($this->output, sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $value), $value, [$value]);
            }
        }
        else {
            $questionHelper = $this->getHelper('question');
            foreach ($parameters['parameters'] as $key => $value) {
                $question = new \Symfony\Component\Console\Question\Question(sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $value), $value, [$value]);
                $serverParameters[$key] = $questionHelper->ask($this->input, $this->output, $question);
            }
        }

        $dumper = new Dumper();
        $parametersYaml = $dumper->dump(['parameters' => $serverParameters], 2);
        $this->sshClient->exec(sprintf('mkdir -p %s/config', $this->server['connection']['path']));
        $this->sshClient->exec(sprintf('printf "%s" > %s/config/parameters.yml', $parametersYaml, $this->server['connection']['path']));
    }

    /**
     * Installs required dependencies: Git
     */
    private function installDependencies()
    {
        // Git
        try {
            $this->sshClient->exec('git -v');
        }
        catch (\Exception $e) {
            $this->output->writeln('<info>Installing Git... Please wait...</info>');
            $this->sshClient->exec('yes | apt-get install git --fix-missing');
        }
    }
}
