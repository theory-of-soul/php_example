<?php
namespace Models;

use Models\Application;

class Banner extends AbstractModel {
    protected $id;
    protected $url;
    protected $locale;
    protected $img;
    protected $hit;
    protected $click;
    protected $num;
    protected $weight;

    public function getWrapper() {
        return $this->app->getObjectCache()->getBannerWrapper();
    }
}
