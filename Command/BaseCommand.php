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

use Seferov\DeployerBundle\Deployer\SshClient;
use Seferov\DeployerBundle\Deployer\Versioner;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseCommand
 * @package Seferov\DeployerBundle\Command
 * @author Farhad Safarov <http://ferhad.in>
 */
abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var SshClient
     */
    protected $sshClient;

    /**
     * @var Versioner
     */
    protected $versioner;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $server;

    /**
     * @var string
     */
    protected $versionsDir;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
                'server_name',
                InputArgument::REQUIRED,
                'Which server?'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->config = $this->getContainer()->getParameter('seferov_deployer_config');

        if (!array_key_exists($this->input->getArgument('server_name'), $this->config['servers'])) {
            throw new \InvalidArgumentException(sprintf('There is no such server named "%s" in the configuration', $this->input->getArgument('server_name')));
        }

        $this->server = $this->config['servers'][$this->input->getArgument('server_name')];
        $this->versionsDir = $this->server['connection']['path'].'/versions/';

        $this->sshClient = $this->getContainer()->get('seferov_deployer.ssh_client');
        $this->sshClient->setOutput($this->output);
        $this->sshClient->connect($this->server['connection']);

        $this->versioner = new Versioner($this->versionsDir, $this->sshClient);

        $this->executeCommand();
    }

    /**
     * @return mixed
     */
    abstract protected function executeCommand();
}
