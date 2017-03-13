<?php

namespace FtpGitSync\Commands;

class Diff extends Command
{

    protected function configure()
    {
        $this
            ->setName('diff')
            ->setDescription('view diff commits');
    }

    protected function fire()
    {
        if (!$this->checkConfigFile()) {
            $this->error("No has inicializado el archivo de configuración. Ejecuta el comando init");
            die;
        }

        $environment = $this->selectDB("¿Con que environment quieres comparar?");

        $this->getDiff($environment);

    }

    protected function getDiff($environment)
    {
        $this->setGit();
        $this->setFtp($environment);

        $commits = $this->git->getCommits();
        $commits = array_keys($commits);
        $commits_server = $this->getCommits();

        if (!$commits_server) {
            $this->error('No se ha iniciado el proyecto en el servidor');
            $this->comment("Inicializa el proyecto en el servidor. Usa el comando start");
            return;
        }

        $diffs = (array_diff($commits, $commits_server));

        if (count($diffs) <= 0) {
            $this->success('Todo esta actualizado');
        } else {
            $this->comment("Hay " . count($diffs) . " commits para actualizar");
            $this->comment('Los archivos a actualizar son:');
            $this->comment(print_r($this->git->getDiff(count($diffs)), true));
        }
    }

}