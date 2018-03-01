<?php

namespace Inviqa;

use Inviqa\Patch\Patch;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;

class Patcher
{
    const PATCH_DIRECTORY = 'composer-patches';

    /** @var ConsoleOutput */
    private $output;

    /** @var \Composer\Script\Event  */
    private $event;

    /**
     * @param \Composer\Script\Event $event
     * @return void
     */
    public function patch(\Composer\Script\Event $event)
    {
        $this->init($event);

        $finder = new Finder();
        $finder->files()->name('*.patch')->in(self::PATCH_DIRECTORY);

        foreach ($finder as $patchFile) {
            $patch = new Patch($patchFile);
            $patch->setOutput($this->output);
            $this->applyPatch($patch);
        }
    }

    /**
     * @param \Composer\Script\Event $event
     * @return void
     */
    private function init(\Composer\Script\Event $event)
    {
        $this->output = new ConsoleOutput();

        if ($event->getIo()->isDebug()) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        $this->event  = $event;
    }

    /**
     * @param Patch $patch
     * @return void
     */
    private function applyPatch(Patch $patch)
    {
        try {
            $patch->apply();
        } catch (\Exception $e) {
            $this->output->writeln("<error>Error applying patch {$patch->getNamespace()}:</error>");
            $this->output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
