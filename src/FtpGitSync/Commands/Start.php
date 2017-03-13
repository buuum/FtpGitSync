<?php

namespace FtpGitSync\Commands;

use Buuum\Zip\Zip;

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

        if ($commits = $this->getCommits()) {
            $this->error('ya esta iniciado el proyecto en el servidor');
        } else {
            $this->success('iniciamos el proyecto en el servidor');

            $files = $this->rglob($this->dir_root() . '/*');
            $files = $this->ignoreFiles($files);

            // create a zip
            $this->zip_name = time() . '_deploy.zip';
            $zip_path = $this->dir_root() . '/' . $this->zip_name;
            $zip = Zip::create($zip_path);

            foreach ($files as $n => $file) {
                $re = '@^(httpdocs)(?=/.*)@';
                $rfile = str_replace($this->dir_root() . '/', '', $file);
                $rfile = preg_replace($re, $environment['public_folder'], $rfile);
                $zip->add($file, $rfile);
            }

            $zip->add($this->dir_root() . '/.htaccess', '.htaccess');
            $zip->add($this->dir_root() . '/httpdocs/.htaccess', $environment['public_folder'] . '/.htaccess');
            $zip->add($this->dir_root() . '/httpdocs/.maintenance.php',
                $environment['public_folder'] . '/.maintenance.php');

            $zip->close();


            $this->ftp->put($environment['public_folder'] . '/Zip.php',
                $this->dir_root() . '/vendor/buuum/zip/src/Zip/Zip.php');
            $this->ftp->put($this->zip_name, $zip_path);
            unlink($zip_path);

            $temp_unzip_path = __DIR__ . '/_un';

            $file_unzip = str_replace('{{zip_name}}', $this->zip_name,
                file_get_contents(__DIR__ . '/../../app/unzip.php.dist'));

            file_put_contents($temp_unzip_path, $file_unzip);

            $this->ftp->put($environment['public_folder'] . '/unzip.php', $temp_unzip_path);
            unlink($temp_unzip_path);

            // initalize temp and log folder with 0777
            $this->ftp->mkdir('temp');
            $this->ftp->chmod(0777, 'temp');
            $this->ftp->mkdir('log');
            $this->ftp->chmod(0777, 'log');

            // initialize commits/commits.json
            $temp_commits_path = __DIR__ . '/_c';
            $this->createCommits($temp_commits_path);
            $this->ftp->put('commits/commits.json', $temp_commits_path);
            unlink($temp_commits_path);

            // descomprimimos el zip en servidor
            $host = $environment['url'] . '/unzip.php';
            $this->curl_get_contents($host);

        }
    }


}