<?php

namespace Inviqa\Patch;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Finder\SplFileInfo;

class Patch
{
    /**
     * @var SplFileInfo
     */
    private $fileInfo;

    /**
     * @var Output
     */
    private $output;

    /**
     * Patch constructor.
     * @param SplFileInfo $fileInfo
     */
    public function __construct(SplFileInfo $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    /**
     * @return boolean|null
     * @throws \Exception
     */
    final public function apply()
    {
        if (!$this->isApplied() && $this->canApply()) {
            $res = (bool)$this->doApply();

            if ($res) {
                $this->getOutput()->writeln("<info>Patch {$this->fileInfo->getFilename()} successfully applied.</info>");
            } else {
                $this->getOutput()->writeln("<comment>Patch {$this->fileInfo->getFilename()} was not applied.</comment>");
            }

            return $res;
        }
        return null;
    }

    /**
     * @throws ProcessFailedException
     * @return boolean
     */
    protected function doApply()
    {
        $patchPath = escapeshellarg($this->fileInfo->getRealPath());
        $process = $this->fromShellCommandline("patch -p 1 < $patchPath");
        $process->mustRun();
        return $process->getExitCode() === 0;
    }

    /**
     * @return bool
     */
    protected function canApply()
    {
        $patchPath = escapeshellarg($this->fileInfo->getRealPath());
        $process = $this->fromShellCommandline("patch --dry-run -p 1 < $patchPath");
        try {
            $process->mustRun();
            return $process->getExitCode() === 0;
        } catch (\Exception $e) {
            $this->getOutput()->writeln("<comment>Patch {$this->fileInfo->getFilename()} skipped. Dry-run response was:</comment>");
            $this->getOutput()->writeln("<comment>{$e->getMessage()}</comment>");
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function isApplied()
    {
        $patchPath = escapeshellarg($this->fileInfo->getRealPath());
        $process = $this->fromShellCommandline("patch --dry-run -p 1 -R < $patchPath");
        try {
            $process->mustRun();
            $result = $process->getExitCode() === 0;

            if ($result) {
                $this->getOutput()->writeln("<info>Patch {$this->fileInfo->getFilename()} already applied.</info>");
            }

            return $result;
        } catch (\Exception $e) {
            // Ignore errors on the reverse, since it probably means it wasn't applied.
            return false;
        }
    }

    /**
     * @param string $cmd Command to execute.
     * @return Process
     */
    protected function fromShellCommandline($cmd)
    {
        // Newer Symfony/Process versions use Process::fromShellCommandline().
        // We have to support both because Composer uses an ancient one bundled in the phar.
        if (is_callable([Process::class, 'fromShellCommandline'])) {
            return Process::fromShellCommandline($cmd);
        }
        return new Process($cmd);
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        if (!$this->output) {
            $this->output = new ConsoleOutput();
        }
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->output = $output;
    }
}
