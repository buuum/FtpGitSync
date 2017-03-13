<?php

namespace FtpGitSync;

use FtpGitSync\Commands\Diff;
use FtpGitSync\Commands\Init;
use FtpGitSync\Commands\Start;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FGSyncApplication extends Application
{

    protected $config_file;

    public function __construct($version = '1.0.0')
    {
        parent::__construct("FtpGitSync: Sync ftp project from git.", $version);

        $this->addCommands([
            new Init(),
            new Diff(),
            new Start()
        ]);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        // always show the version information except when the user invokes the help
        // command as that already does it
        if (false === $input->hasParameterOption(array('--help', '-h')) && null !== $input->getFirstArgument()) {
            $output->writeln($this->getLongVersion());
            $output->writeln('');
        }

        return parent::doRun($input, $output);
    }

}