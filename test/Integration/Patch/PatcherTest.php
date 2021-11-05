<?php

namespace Inviqa\Test\Integration\Patch;

use Inviqa\Patch\Patch;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Console\Output\StreamOutput;

class PatchTest extends TestCase
{
    const PATH_PATCHES = 'test/Integration/files';

    /**
     * @var SplFileInfo
     */
    private $fileInfo;

    /**
     * @var bool
     */
    private $revert = false;

    protected function tearDown()
    {
        if (!$this->revert) {
            return;
        }

        $patchPath = escapeshellarg($this->fileInfo->getRealPath());
        if (is_callable([Process::class, 'fromShellCommandline'])) {
            $process = Process::fromShellCommandline("patch -p 1 -R < $patchPath");
        } else {
            $process = new Process("patch -p 1 -R < $patchPath");
        }
        $process->mustRun();
    }

    /**
     * @dataProvider applyDataProvider
     * @param SplFileInfo $fileInfo
     * @param bool $expectApplied
     */
    public function testApply(SplFileInfo $fileInfo, $expectApplied)
    {
        $this->revert = $expectApplied;
        $this->fileInfo = $fileInfo;
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $patcher = new Patch($fileInfo);
        $patcher->setOutput($output);
        $patcher->apply();

        rewind($output->getStream());
        $display = stream_get_contents($output->getStream());

        $this->assertContains(
            $expectApplied ? 'successfully' : 'skipped',
            $display
        );
    }

    public function applyDataProvider()
    {
        $finder = new Finder();
        $finder->files()->name('*.patch')->in(self::PATH_PATCHES);

        $testCases = [];
        foreach ($finder as $patchFile) {
            $testCases[] = [
                $patchFile,
                stripos($patchFile->getFilename(), 'success') !== false,
            ];
        }

        return $testCases;
    }
}
