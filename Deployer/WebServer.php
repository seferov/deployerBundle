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

/**
 * Class WebServer
 * @package Seferov\DeployerBundle\Deployer
 */
class WebServer
{
    /**
     * @var SshClient
     */
    private $sshClient;

    /**
     * @var string
     */
    private $path;

    /**
     * @param SshClient $sshClient
     * @param string $path
     */
    public function __construct(SshClient $sshClient, $path)
    {
        $this->sshClient = $sshClient;
        $this->path = $path;
    }

    public function installApp()
    {
        $this->sshClient->exec(sprintf('mkdir -p %s/web', $this->path));
        $this->sshClient->exec(sprintf('echo "%s" >> /etc/apache2/conf-enabled/000-default.conf', $this->getConfiguration()));
        $this->sshClient->exec('a2enmod rewrite');
        $this->restart();
    }

    public function restart()
    {
        $this->sshClient->exec('/etc/init.d/apache2 restart');
    }

    private function getConfiguration()
    {
        return <<<EOT
<VirtualHost *:80>
    # Generated by SeferovDeployerBundle
    # For more information see: https://github.com/seferov/deployerBundle

    ServerAdmin webmaster@localhost
    DocumentRoot {$this->path}/web

    <Directory {$this->path}/web>
        # enable the .htaccess rewrites
        AllowOverride All
        Order allow,deny
        Allow from All
        DirectoryIndex index.html app.php index.php
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
ServerName localhost

EOT;
    }
}