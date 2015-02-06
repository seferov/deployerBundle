<?php

namespace Seferov\DeployerBundle\Deployer;
use Symfony\Component\Intl\Exception\RuntimeException;

/**
 * Class Versioner
 * @package Seferov\DeployerBundle\Deployer
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
    }

    public function getVersion()
    {
        $out = $this->sshClient->exec("tail '{$this->path}/ondeck/.git/refs/heads/master'");
        if (!count($out)) {
            throw new RuntimeException('Could not get Git version');
        }

        return $out[0];
    }
}