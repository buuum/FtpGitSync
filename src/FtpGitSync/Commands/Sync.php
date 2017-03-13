<?php

namespace FtpGitSync\Commands;

class Sync extends Command
{

    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('sync project in environment');
    }

    protected function fire()
    {
        if (!$this->checkConfigFile()) {
            $this->error("No has inicializado el archivo de configuración. Ejecuta el comando init");
            die;
        }

        $environment = $this->selectDB("¿En que environment quieres sincronizar el proyecto?");

        $this->sync($environment);

        $this->success("Environment sincronizado correctamente.");

    }

    protected function sync($environment)
    {
        $this->setGit();
        $this->setFtp($environment);

        $this->sync_commits();
    }


}