<?php

namespace FtpGitSync\Commands;

class Start extends Command
{

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('start project in environment');
    }

    protected function fire()
    {
        if (!$this->checkConfigFile()) {
            $this->error("No has inicializado el archivo de configuración. Ejecuta el comando init");
            die;
        }

        $environment = $this->selectDB("¿En que environment quieres empezar el proyecto?");

        $this->start($environment);

    }

    protected function start($environment)
    {
        $this->setGit();
        $this->setFtp($environment);

        if (!$this->ftp->chdir('temp')) {
            $this->error("Falta la carpeta temp en el servidor");
            die;
        }
        $this->ftp->cdUp();

        if ($commits = $this->getCommits()) {
            $this->error('ya esta iniciado el proyecto en el servidor');
        } else {
            $this->success('iniciamos el proyecto en el servidor');

            $files = $this->rglob($this->dir_root() . '/*');
            $files = $this->ignoreFiles($files);

            // create a zip
            $zip = $this->createZip();

            $local_public_folder = $this->config['paths']['public_folder'];

            foreach ($files as $n => $file) {
                $re = '@^(' . $local_public_folder . ')(?='. DIRECTORY_SEPARATOR .'.*)@';
                $rfile = str_replace($this->dir_root() . DIRECTORY_SEPARATOR, '', $file);
                $rfile = preg_replace($re, $environment['public_folder'], $rfile);
                $rfile = str_replace(DIRECTORY_SEPARATOR, '/', $rfile);
                $zip->add($file, $rfile);
            }

            $zip->add($this->dir_root() . '/.htaccess', '.htaccess');
            $zip->add($this->dir_root() . '/' . $local_public_folder . '/.htaccess',
                $environment['public_folder'] . '/.htaccess');
            $zip->add($this->dir_root() . '/' . $local_public_folder . '/.maintenance.php',
                $environment['public_folder'] . '/.maintenance.php');

            $zip->close();

            // initalize temp and log folder with 0777
            //$this->ftp->mkdir('temp');
            //$this->ftp->chmod(0777, 'temp');
            //$this->ftp->mkdir('log');
            //$this->ftp->chmod(0777, 'log');

            $this->uploadZip($environment);

            $this->unzip($environment);

            // initialize commits/commits.json
            $temp_commits_path = __DIR__ . '/_c';
            $this->createCommits($temp_commits_path);
            $this->ftp->put('commits/commits.json', $temp_commits_path);
            unlink($temp_commits_path);

        }
    }


}