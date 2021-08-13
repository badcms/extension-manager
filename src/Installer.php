<?php

namespace BadCMS\ExtensionManager;

use Composer\Composer;
use Composer\Installer\InstallerInterface;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use InvalidArgumentException;
use React\Promise\PromiseInterface;

class Installer extends LibraryInstaller implements InstallerInterface
{
    protected $pluginsDB = "/config/plugins.php";

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        list(, $prefix) = explode("/", $package->getPrettyName());

        if (strpos($prefix, 'badcms-ext-') === false) {
            throw new \InvalidArgumentException(
                'Unable to install plugin, BadCMS plugins '
                .'should always start their package name with '
                .'"vendor/badcms-ext-"'
            );
        }

        return 'plugins/'.$package->getPrettyName();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'badcms-extension' === $packageType;
    }

    private function readDB()
    {
        $projectRootPath = dirname($this->vendorDir);

        if (file_exists($pluginsConfigFile = $projectRootPath.$this->pluginsDB)) {
            return include $pluginsConfigFile;
        }

        return [];
    }

    private function saveDB($plugins)
    {
        $projectRootPath = dirname($this->vendorDir);

        $plugins = "<?php\n return ".var_export($plugins, true).";";
        $pluginsConfigFile = $projectRootPath.$this->pluginsDB;

        if (!file_exists($configDir = dirname($pluginsConfigFile))) {
            mkdir($configDir);
        }

        return file_put_contents($pluginsConfigFile, $plugins);
    }

    private function addPlugin($package)
    {
        $name = $package["name"];
        $description = $package["description"] ?? "";

        echo PHP_EOL."\e[35m[ BadCMS - \e[32mInstalling Plugin \e[33m".$name."\e[32m - \e[36m$description \e[35m ]\e[39m".PHP_EOL;

        $plugins = $this->readDB();
        if (!$plugins) {
            $plugins = [];
        }
        $plugins[$name] = $package;
        $this->saveDB($plugins);
    }

    private function removePlugin($name)
    {
        $plugins = $this->readDB();
        if (isset($plugins[$name])) {
            echo "\n - Remove Plugin ".$name.PHP_EOL;

            unset($plugins[$name]);
            $this->saveDB($plugins);
        }
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $promise = parent::install($repo, $package);

        return $promise->then(function () use ($package, $repo) {
            $extra = $package->getExtra();
            $this->addPlugin([
                "name" => $package->getName(),
                "version" => $package->getVersion(),
                "namespace" => isset($extra["namespace"]) ? $extra["namespace"] : null,
                "description" => isset($extra["description"]) ? $extra["description"] : "",
            ]);
        });
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $promise = parent::uninstall($repo, $package);

        return $promise->then(function () use ($package) {
            $this->removePlugin($package->getName());
        });
    }

}
