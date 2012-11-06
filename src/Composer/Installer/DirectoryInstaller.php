<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Installer;

use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;

/**
 * Directory package installation manager.
 *
 * @author Freek Gruntjes <freek@gruntjes.net>
 */
class DirectoryInstaller extends MetapackageInstaller
{
    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === 'directory';
    }

	/**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
		return realpath($package->getSourceUrl());
    }
}
