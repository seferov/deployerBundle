<?php

namespace Seferov\DeployerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Exception\InvalidArgumentException;
use Seferov\DeployerBundle\Deployer\Versioner;

/**
 * Class RollbackCommand
 * @package Seferov\DeployerBundle\Command
 */
class RollbackCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('deployer:rollback')
            ->addArgument(
                'server_name',
                InputArgument::REQUIRED,
                'To which server do you want to rollback?'
            )
            ->setDescription('Rollbacks to the previous version')
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

        // Versioner
        $versioner = new Versioner($versionsDir, $sshClient);
        $previousVersion = $versioner->getPreviousVersion();

        // Symlink
        $sshClient->exec(sprintf('rm %s/web', $server['connection']['path']));
        $sshClient->exec(sprintf('ln -s %s/web %s', $versionsDir . $previousVersion, $server['connection']['path']));
        $versioner->setNewVersion($previousVersion);

        $output->writeln('<comment>Successfully done!</comment>');
    }
}