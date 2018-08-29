<?php

namespace Providers\Config;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Parser;
use Helpers\ArrayHelper;

/**
 * Config.
 */
class ConfigProvider implements ServiceProviderInterface {

    private $configPath;
    private $app;

    /**
     * Constructor.
     *
     */
    public function __construct() {
        $this->configPath = ROOT . '/config';
    }

    /**
     * Register.
     *
     * @param Application $app Application.
     *
     * @throws \Exception Yaml parse error.
     *
     * @return void
     */
    public function register(Application $app) {

        $yml = new Parser();
        try {

            $config = $yml->parse(file_get_contents($this->configPath . "/config_common.yml"));
            $config = $this->addLocalConfig($config);

            $app["config"] = $config;
            $app["configProvider"] = $this;


            $this->getDeployData($app);
            $this->app = $app;

        } catch (\Exception $e) {
            throw new \Exception("Unable to parse the YAML string: " . $e->getMessage());
        }

    }

    public function addValue(array $arr) {
        $c = array_slice($this->app['config'], 0);
        $this->app['config'] = ArrayHelper::extend($c, $arr);
    }

    private function addLocalConfig(array $config) {
        try {
            $yml = new Parser();
            $testConfig = $yml->parse(file_get_contents($this->configPath . "/config.yml"));
            $config = array_replace_recursive($config, $testConfig);
            return $config;
        } catch (\Exception $e) {
            throw new \Exception("Unable to parse the YAML string: " . $e->getMessage());
        }
    }

    /**
     * Set test config.
     *
     * @param array $config Config.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getTestConfig(array $config) {
        $fileName = $this->configPath . "/test_config.yml";
        if (file_exists($fileName)) {
            try {
                $yml = new Parser();
                $testConfig = $yml->parse(file_get_contents($fileName));
                $config = array_replace_recursive($config, $testConfig);
            } catch (\Exception $e) {
                throw new \Exception("Unable to parse the YAML string: " . $e->getMessage());
            }
        }

        return $config;
    }

    public function getResourceConfig(Application $app) {
        $yml = new Parser();
        $resources = $yml->parse(file_get_contents($this->configPath . "/resources.yml"));
        $resources = $resources['PATHS'];
        array_walk_recursive($resources, function (&$item, $key) {
            $item = str_replace('./web/', '/', $item);
        });
        $app['resources'] = $resources;
        return $app['resources'];
    }

    public function getDeployData(Application $app) {
        $releaseInfo = $this->configPath . "/release.info";
        $maintenanceFile = $this->configPath . '/maintenance.lock';
        $deployData = array(
            'build_id' => '-',
            'branch' => 'local',
            'version' => time(),
            'maintenance' => file_exists($maintenanceFile) ? trim(file_get_contents($maintenanceFile)) : false
        );
        if (file_exists($releaseInfo))
            $deployData = array_merge($deployData, array_combine(
                    array('build_id', 'branch', 'version'), (array)@file($releaseInfo, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES))
            );
        return $app['deploy'] = $deployData;
    }

    /**
     * Boot.
     *
     * @param Application $app Application.
     *
     * @return void
     */
    public function boot(Application $app) {
        $this->app = $app;
    }
}
