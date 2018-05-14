<?php

namespace FtpGitSync\Commands;

use Buuum\Ftp\Connection;
use Buuum\Ftp\FtpWrapper;
use Buuum\Ftp\SSLConnection;
use Buuum\Git;
use Buuum\Zip\Zip;
use Curl\Curl;
use Symfony\Component\Yaml\Yaml;

class Command extends AbstractCommand
{

    /**
     * @var string
     */
    protected $config_file_name = 'fgsync.yml';

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var FtpWrapper
     */
    protected $ftp;

    /**
     * @var Git
     */
    protected $git;

    /**
     * @var string
     */
    protected $zip_name;

    /**
     * @var string
     */
    protected $zip_path;

    protected function selectDB($question)
    {
        $environments_list = [];
        $environments_by_host = [];

        foreach ($this->config['environments'] as $name => $config) {
            $environments_list[] = $name;
            $environments_by_host[$name] = $config;
        }

        $environment_host = $this->choiceQuestion("$question\n", $environments_list);

        return $environments_by_host[$environment_host];
    }

    protected function dir_root()
    {
        return realpath(getcwd());
    }

    protected function checkConfigFile()
    {
        $file = $this->getConfigFile();
        if (file_exists($file)) {
            $this->config = Yaml::parse(file_get_contents($file));
            return $file;
        }
        return false;
    }

    protected function getConfigFile()
    {
        return $this->dir_root() . '/' . $this->config_file_name;
    }

    protected function getPath($name)
    {
        if (!isset($this->config['paths'][$name])) {
            $this->error("No esta definido el path $name");
            die;
        }
        $path = $this->dir_root() . '/' . $this->config['paths'][$name] . '/';
        $path = str_replace('//', '/', $path);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    protected function getConfigDefault()
    {
        return __DIR__ . '/../../app/sync.yml';
    }

    protected function setGit()
    {
        $this->git = new Git($this->dir_root());
    }

    protected function setFtp($environment)
    {

        $host = $environment['host'];
        $username = $environment['user'];
        $password = $environment['password'];
        $port = $environment['port'];
        $timeout = $environment['timeout'];
        $passive = $environment['passive'];
        $connection_type = $environment['connection'];

        if ($connection_type == 'ssl') {
            $connection = new SSLConnection($host, $username, $password, $port, $timeout, $passive);
        } else {
            $connection = new Connection($host, $username, $password, $port, $timeout, $passive);
        }

        try {
            $connection->open();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            die;
        }

        $this->ftp = new FtpWrapper($connection);

    }

    protected function getCommits()
    {
        $temp = $this->dir_root();
        $temp .= '/commits.json';
        $this->comment($temp);

        if (@$this->ftp->get($temp, 'commits/commits.json')) {
            $commits = json_decode(file_get_contents($temp));
            unlink($temp);
            return $commits;
        }

        return false;
    }

    protected function rglob($pattern = '*', $flags = 0, $path = false)
    {
        if (!$path) {
            $path = dirname($pattern) . DIRECTORY_SEPARATOR;
        }
        $pattern = basename($pattern);
        $paths = glob($path . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);
        foreach ($paths as $path) {
            $files = array_merge($files, $this->rglob($pattern, $flags, $path));
        }
        return $files;
    }

    protected function ignoreFiles($files)
    {
        $files_upload = [];
        $base_path = $this->dir_root();

        $ignore_folders = $this->config['ignore']['files'];

        $ignore_files = $this->config['ignore']['folders'];

        foreach ($files as $file) {

            $ignore = false;

            if (is_dir($file)) {
                continue;
            }

            foreach ($ignore_folders as $ignore_folder) {
                if (strpos($file, $base_path . DIRECTORY_SEPARATOR . $ignore_folder) !== false) {
                    $ignore = true;
                    break;
                }
            }
            if ($ignore) {
                continue;
            }

            foreach ($ignore_files as $ignore_file) {
                if (strpos($file, $base_path . DIRECTORY_SEPARATOR . $ignore_file) !== false) {
                    $ignore = true;
                    break;
                }
            }
            if ($ignore) {
                continue;
            }

            $files_upload[] = $file;

        }

        return $files_upload;
    }

    protected function createCommits($temp_commits_path)
    {

        $commits = $this->git->getCommits();
        $commits = array_keys($commits);
        $coms = [];
        foreach ($commits as $commit) {
            $coms[] = $commit;
        }
        file_put_contents($temp_commits_path, json_encode($coms));
    }

    protected function curl_get_contents($host)
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->get($host);
    }

    protected function createZip()
    {
        $this->zip_name = time() . '_deploy.zip';
        $this->zip_path = $this->dir_root() . '/' . $this->zip_name;
        return Zip::create($this->zip_path);
    }

    protected function uploadZip($environment)
    {

        $this->ftp->put($environment['public_folder'] . '/Zip.php',
            $this->dir_root() . '/vendor/buuum/zip/src/Zip/Zip.php');
        $this->ftp->put('temp/' . $this->zip_name, $this->zip_path);
        unlink($this->zip_path);

        $temp_unzip_path = __DIR__ . '/_un';

        $file_unzip = str_replace('{{zip_name}}', $this->zip_name,
            file_get_contents(__DIR__ . '/../../app/unzip.php.dist'));

        file_put_contents($temp_unzip_path, $file_unzip);

        $this->ftp->put($environment['public_folder'] . '/unzip.php', $temp_unzip_path);
        unlink($temp_unzip_path);

    }

    protected function unzip($environment)
    {
        // descomprimimos el zip en servidor
        $host = $environment['url'] . '/unzip.php';
        $this->curl_get_contents($host);
    }

    protected function sync_commits()
    {
        $temp_commits_path = $this->dir_root() . '/_c';
        $this->createCommits($temp_commits_path);
        $this->ftp->put('commits/commits.json', $temp_commits_path);
        unlink($temp_commits_path);

    }

    protected function fire()
    {
    }


}