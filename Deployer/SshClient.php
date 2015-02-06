<?php

namespace Seferov\DeployerBundle\Deployer;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SshClient
 * @package Seferov\DeployerBundle\Deployer
 */
class SshClient
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var resource
     */
    private $shell;

    /**
     * @param array $server
     */
    public function connect(array $server)
    {
        $this->stream = ssh2_connect($server['host'], $server['port']);

        if (!$this->stream) {
            throw new \InvalidArgumentException(sprintf('SSH connection failed on "%s:%s"', $server['host'], $server['ssh_port']));
        }

        if (!ssh2_auth_password($this->stream, $server['username'], $server['password'])) {
            throw new \InvalidArgumentException(sprintf('SSH authentication failed for user "%s"', $server['username']));
        }

        $this->shell = ssh2_shell($this->stream);
        if (!$this->shell) {
            throw new \RuntimeException('Failed opening shell');
        }
    }

    /**
     * @param $command
     * @param bool $display
     * @return array
     * @throws \Exception
     */
    public function exec($command, $display = true)
    {
        $outStream = ssh2_exec($this->stream, $command);
        $errStream = ssh2_fetch_stream($outStream, SSH2_STREAM_STDERR);

        stream_set_blocking($outStream, true);
        stream_set_blocking($errStream, true);

        $err = $this->removeEmptyLines(explode("\n", stream_get_contents($errStream)));
        if (count($err)) {
            if (strpos($err[0], 'Cloning into') === false) {
                throw new \Exception(implode("\n", $err));
            }
        }

        $out = $this->removeEmptyLines(explode("\n", stream_get_contents($outStream)));

        fclose($outStream);
        fclose($errStream);

        if ($display) {
            if (!$this->output instanceof OutputInterface) {
                throw new \LogicException('You should set output first');
            }

            foreach ($out as $line) {
                $this->output->writeln(sprintf('<info>%s</info>', $line));
            }
        }

        return $out;
    }

    private function removeEmptyLines(array $lines)
    {
        foreach ($lines as $key => $line) {
            if ($line == '') {
                unset($lines[$key]);
            }
        }

        return $lines;
    }

    /**
     * @param $file
     * @param $path
     */
    public function upload($file, $path)
    {
        ssh2_scp_send($this->stream, $file, $path);
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}