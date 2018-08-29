<?php
namespace Providers\GeoProvider;

use Silex\ServiceProviderInterface;
use Models\Application;
use Models\GeoData;

class GeoProvider implements ServiceProviderInterface {
    private $app;
    private $ip;
    private $geoData;

    private function requestIpInfo() {
        $this->geoData = null;
        if (!filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            if ( !$data = file_get_contents( str_replace('{$ip}',$this->ip,$this->app['config']['geoUrl']) . $this->app['config']['geoToken'] ) ) 
            {
                $error = error_get_last();
                $this->app->getLoggerProvider()->error("GeoIP API Error: $error");
            } else {
                
                $this->geoData = json_decode(preg_replace('/\s+/', '', $data), true);
                $this->saveCache();
            }
        }
    }

    private function requestCache() {
        if(!isset($this->geoData['ip']) || $this->geoData['ip'] != $this->ip) {
            $geoData = $this->app->getObjectCache()->getGeoDataWrapper()->getByIp($this->ip);
            if($geoData) {
                $this->geoData = $geoData->getData();
            } else {
                $this->geoData = null;
            }
        }
    }

    private function fillGeoData($ip = null) {
        if($ip === null) {
            $ip = $this->getIpFromRequest();
        }

        $this->setIp($ip);
        $this->requestCache();

        if(!$this->geoData) {
            $this->requestIpInfo();
        }
    }

    private function saveCache() {
        $geoData = new GeoData($this->app);
        $geoData->setIp($this->ip);
        $geoData->setData($this->geoData);
        $geoData->save();
    }

    public function getIpFromRequest() {
        $server = $this->app->getRequest()->server;
        return $server->get('HTTP_X_REAL_IP') ?:
            $server->get('HTTP_CLIENT_IP') ?:
                $server->get('HTTP_X_FORWARDED_FOR') ?
                    explode(',', $server->get('HTTP_X_FORWARDED_FOR'))[0] :
                    $server->get('REMOTE_ADDR') ?: null;
    }

    public function setIp($ip) {
        $this->ip = trim($ip);
    }

    public function getCity($ip = null) {
        $this->fillGeoData($ip);
        return isset($this->geoData['city']) ? $this->geoData['city'] : null;
    }

    public function getCountry($ip = null) {
        $this->fillGeoData($ip);
        return isset($this->geoData['country']) ? $this->geoData['country'] : null;
    }

    public function register(\Silex\Application $app) {
        $app['geo'] = $this;
        $this->app = $app;
    }

    public function boot(\Silex\Application $app) {
    }
}