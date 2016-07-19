<?php

namespace NwLaravel\Process;

use Symfony\Component\Process\Process;
use \RuntimeException;
use \Exception;

/**
 * Class Background
 */
class Background
{
    /**
     * @var string
     */
    protected $errors;

    /**
     * @var string
     */
    protected $pid;

    /**
     * @var string
     */
    protected $log = '/dev/null';

    /**
     * Construct
     *
     * @param string $log String Log
     */
    public function __construct($log = null)
    {
        if ((file_exists($log) && is_writable($log)) ||
            (!file_exists($log) && is_writable(dirname($log)))
        ) {
            $this->setLog($log);
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    protected function init()
    {
        $this->pid = null;
        $this->errors = null;
    }

    /**
     * Start Process Background
     *
     * @param string $cmd String Cmd
     *
     * @return bool
     */
    public function start($cmd)
    {
        $this->init();

        $process = new Process($cmd.' >> '.$this->log.' 2>&1');
        $process->disableOutput();
        $process->start();

        if ($process->isRunning()) {
            sleep(1);
            $this->pid = $this->pid($cmd);
        }

        return true;
    }

    /**
     * Stop Process Background
     *
     * @param string $cmd String Cmd
     *
     * @return bool
     */
    public function stop($cmd)
    {
        $pid = $this->pid($cmd);
        if ($pid) {
            exec(sprintf("kill -9 %s", $pid));
        }

        $this->pid = null;

        return true;
    }

    /**
     * Busca Pid do Cmd
     *
     * @param string $cmd String Cmd
     *
     * @return this
     */
    public function pid($cmd)
    {
        return exec(sprintf("ps -aefw | grep '%s' | grep -v ' grep ' | awk '{print $2}'", $cmd));
    }

    /**
     * Get Pid
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Gets the value of log.
     *
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Sets the value of log.
     *
     * @param mixed $log Mixed Log
     *
     * @return self
     */
    public function setLog($log)
    {
        if ($log != '/dev/null' && (!is_writable(dirname($log)) || !is_writable(dirname($log)))) {
            throw new RuntimeException(sprintf('Error: Path "%s" not writable.', $log));
        }

        $this->log = $log;

        return $this;
    }

    /**
     * Gets the value of error
     *
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
