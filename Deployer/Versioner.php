<?php

/*
 * This file is part of the SeferovDeployerBundle package.
 *
 * (c) Farhad Safarov <http://ferhad.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\DeployerBundle\Deployer;

use Symfony\Component\Intl\Exception\RuntimeException;

/**
 * Class Versioner
 * @package Seferov\DeployerBundle\Deployer
 * @author Farhad Safarov <http://ferhad.in>
 */
class Versioner
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var SshClient
     */
    private $sshClient;

    /**
     * @param $path
     * @param SshClient $sshClient
     */
    public function __construct($path, SshClient $sshClient)
    {
        $this->path = $path;
        $this->sshClient = $sshClient;
        $this->versionsFile = $this->path . 'versions.txt';
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getAppVersion()
    {
        $out = $this->sshClient->exec("tail '{$this->path}/ondeck/.git/refs/heads/master'");
        if (!count($out)) {
            throw new RuntimeException('Could not get Git version');
        }

        return $out[0];
    }

    /**
     * @param $version
     */
    public function setNewVersion($version)
    {
        // Add version to the top of file
        $this->sshClient->exec("echo '{$version}' | cat - ".$this->versionsFile." > temp && mv temp ".$this->versionsFile);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getPreviousVersion()
    {
        $versions = $this->sshClient->exec(sprintf('head -2 %s', $this->versionsFile));
        return end($versions);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getCurrentVersion()
    {
        $versions = $this->sshClient->exec(sprintf('head -1 %s', $this->versionsFile));
        if (count($versions)) {
            return $versions[0];
        }
        return false;
    }
}