<?php

namespace BadCMS\ExtensionManager;

use Composer\Composer;
use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use BadCMS\ExtensionManager\Installer as BaseInstaller;
use Composer\Util\Filesystem;

class InstallerComposerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new BaseInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        //
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        //
    }
}
