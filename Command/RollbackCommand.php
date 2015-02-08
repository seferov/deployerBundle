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
 * Class RollbackCommand
 * @package Seferov\DeployerBundle\Command
 * @author Farhad Safarov <http://ferhad.in>
 */
class RollbackCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('deployer:rollback')
            ->setDescription('Rollbacks to the previous version')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        $previousVersion = $this->versioner->getPreviousVersion();

        // Symlink
        $this->sshClient->exec(sprintf('rm %s/web', $this->server['connection']['path']));
        $this->sshClient->exec(sprintf('ln -s %s/web %s', $this->versionsDir . $previousVersion, $this->server['connection']['path']));
        $this->versioner->setNewVersion($previousVersion);

        $this->output->writeln('<comment>Successfully done!</comment>');
    }
}