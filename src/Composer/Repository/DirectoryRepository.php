<?php

namespace Composer\Repository;

use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Json\JsonFile;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use SplFileInfo;
use DateTime;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * @author Freek Gruntjes <freek@gruntjes.net>
 */
class DirectoryRepository extends ArrayRepository
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @param array $repoConfig
     * @param IOInterface $io
     */
    public function __construct(array $repoConfig, IOInterface $io)
    {
        $this->root = isset($repoConfig['root']) ? $repoConfig['root'] : getcwd();
        $this->io = $io;
    }

    /**
     * Scan root for packages recursive
     */
    public function initialize()
    {
        parent::initialize();

        if(!is_dir($this->root))
        {
            throw new \RuntimeException('Could read directory '.$this->root);
        }

        $loader = new ArrayLoader();
        $composerFiles = new RecursiveDirectoryIterator($this->root, null);
        $composerFiles = new RecursiveIteratorIterator($composerFiles);
        $composerFiles = new FilenameFilterIterator($composerFiles, array('composer\.json'), array());
        /** @var $file SplFileInfo */
        foreach ($composerFiles as $file)
        {
            $jsonFile = new JsonFile($file->getPathname());
            $packageData = $jsonFile->read();
            if (!is_array($packageData)) {
                throw new \UnexpectedValueException('Could not parse package list from the '.$jsonFile->getPath().' repository');
            }

            $generatedPackageInfo = $this->getGeneratedPackageInfo($file->getPath());

            // Add time to package if none existing
            if (!isset($packageData['time'])) {
                // Set last updated file as time
                $packageData['time'] = date("Y-m-d H:i:s", $generatedPackageInfo['lastModified']);
            }

            if (!isset($packageData['source']))
            {
                $packageData['source'] = array(
                    'type' => 'directory',
                    'url' => $file->getPath(),
                    'reference' => $generatedPackageInfo['crc'],
                );
            }

            $package = $loader->load($packageData);

            $this->addPackage($package);
        }
    }

    /**
     * Get newest last modified date from all files in directory
     *
     * @param string $rootDirectory
     * @return array
     */
    private function getGeneratedPackageInfo($rootDirectory)
    {
        $directory = new RecursiveDirectoryIterator($rootDirectory, null);
        $directory = new RecursiveIteratorIterator($directory);

        $lastModDate = null;
        $md5 = '';
        /** @var $file SplFileInfo */
        foreach($directory as $file)
        {
            if($file->getFilename() === '.' || $file->getFilename() === '..')
            {
                continue;
            }

            if($lastModDate === null || $lastModDate < $file->getMTime())
            {
                $lastModDate = $file->getMTime();
            }

            $md5 .= md5_file($file->getPathname());
        }

        return array(
            'crc' => md5($md5),
            'lastModified' => $lastModDate
        );
    }
}