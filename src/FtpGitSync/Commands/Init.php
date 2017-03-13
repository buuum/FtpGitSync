<?php

namespace FtpGitSync\Commands;

class Init extends Command
{

    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('initialize yaml configuration file');
    }

    protected function fire()
    {
        $write = true;
        if ($file = $this->checkConfigFile()) {
            $this->error("El archivo de configuración ya está creado.");
            $overwrite = $this->choiceQuestion("¿Quieres sobreescribirlo?", ["Si", "No"]);
            if ($overwrite == "No") {
                $write = false;
            }
        }
        if ($write) {
            file_put_contents($this->getConfigFile(), file_get_contents($this->getConfigDefault()));
            $this->success("Archivo de configuración creado correctamente.");
        }
    }

}