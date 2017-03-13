<?php

namespace FtpGitSync\Commands;

class Update extends Command
{

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update project in environment');
    }

    protected function fire()
    {
        if (!$this->checkConfigFile()) {
            $this->error("No has inicializado el archivo de configuración. Ejecuta el comando init");
            die;
        }

        $environment = $this->selectDB("¿En que environment quieres actualizar el proyecto?");

        $this->update($environment);

    }

    protected function update($environment)
    {
        $this->setGit();
        $this->setFtp($environment);

        $commits = $this->git->getCommits();
        $commits = array_keys($commits);
        $commits_server = $this->getCommits();

        $diffs = (array_diff($commits, $commits_server));

        if (count($diffs) <= 0) {
            $this->success('Todo esta actualizado!');
        } else {

            $base_path = $this->dir_root();

            $files = $this->git->getDiff(count($diffs));

            $files = $this->parseGitFiles($files);

            $update = !empty($files);

            if (!empty($files['delete'])) {
                $this->comment('elementos a borrar');
                foreach ($files['delete'] as $file) {
                    $this->ftp->delete($file);
                    $this->success("$file > eliminado");
                }
            }

            if (!empty($files['add'])) {

                $this->comment('elementos a subir');

                $zip = $this->createZip();

                $local_public_folder = $this->config['paths']['public_folder'];

                foreach ($files['add'] as $file) {
                    $re = '@^(' . $local_public_folder . ')(?=/.*)@';
                    $filer = preg_replace($re, $environment['public_folder'], $file);
                    $zip->add($base_path . '/' . $file, $filer);
                    $this->success("$file > adjuntado");
                }

                $zip->close();

                $this->uploadZip($environment);
                $this->unzip($environment);

            }

            if ($update) {
                // update commits
                $this->sync_commits();
                $this->success("Todo esta actualizado.");
            }

        }
    }

    private function parseGitFiles($files)
    {
        $files_ = [];
        foreach ($files as $file) {
            $type = substr($file, 0, 1);
            $file = trim(substr($file, 1));
            if ($type == 'M' || $type == 'A') {
                $files_['add'][] = $file;
            } elseif ($type == 'D') {
                $files_['delete'][] = $file;
            }
        }
        return $files_;
    }


}