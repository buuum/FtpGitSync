<?php

namespace FtpGitSync\Commands;

class Vendor extends Command
{

    protected function configure()
    {
        $this
            ->setName('vendor')
            ->setDescription('Update vendor project in environment');
    }

    protected function fire()
    {
        if (!$this->checkConfigFile()) {
            $this->error("No has inicializado el archivo de configuración. Ejecuta el comando init");
            die;
        }

        $environment = $this->selectDB("¿En que environment quieres actualizar los vendor?");

        $option = $this->selectOptionVendor();

        $this->setFtp($environment);

        $zip = $this->createZip();

        $path = $this->dir_root();

        if ($option != 'actualizar todo') {
            $files = $this->rglob($path . '/vendor/' . $option . '*');

            foreach ($files as $n => $file) {
                $rfile = str_replace($path . '/', '', $file);
                if (!is_dir($file)) {
                    $zip->add($file, $rfile);
                }
            }

            $files = $this->rglob($path . '/vendor/composer/*');

            foreach ($files as $n => $file) {
                $rfile = str_replace($path . '/', '', $file);
                if (!is_dir($file)) {
                    $zip->add($file, $rfile);
                }
            }

            $zip->add($path . '/vendor/autoload.php', 'vendor/autoload.php');

        } else {
            $files = $this->rglob($path . '/vendor/*');
            foreach ($files as $n => $file) {
                $rfile = str_replace($path . '/', '', $file);
                if (!is_dir($file)) {
                    $zip->add($file, $rfile);
                }
            }
        }

        $zip->close();

        $this->uploadZip($environment);
        $this->unzip($environment);

    }

    protected function selectOptionVendor()
    {
        $path = $this->dir_root();
        $paths = glob($path . '/vendor/*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
        $folders = [];
        foreach ($paths as $_path) {
            $folders[] = str_replace($path . '/vendor/', '', $_path);
        }
        $folders[] = 'actualizar todo';
        return $this->choiceQuestion('¿Que quieres actualizar?', $folders);
    }

}