<?php

namespace Models;

class Channel extends AbstractModel {
    protected $id;
    protected $title;
    protected $description;
    protected $description_en;
    protected $enabled;
    protected $visible;
    protected $language;
    protected $uptime;
    protected $epg;

    public function getWrapper() {
        return $this->app->getObjectCache()->getChannelWrapper();
    }

    public function getEvents() {
        return $this->getWrapper()->getEvents($this);
    }

    public function getErrors() {
        $errors = [];
        if(!$this->title) {
            $errors[] = 'Введите название канала.';
        }
        return $errors;
    }

    public function beforeSave() {
        $this->enabled = (int)$this->enabled;
        $this->visible = (int)$this->visible;
    }
}