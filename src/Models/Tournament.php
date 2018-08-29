<?php

namespace Models;

class Tournament extends AbstractModel {
    protected $id;
    protected $title;
    protected $title_en;
    protected $sport_id;

    public function getWrapper() {
        return $this->app->getObjectCache()->getTournamentWrapper();
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function setTitle($title, $locale = 'ru') {
        if ($locale !== 'ru')
            $this->title_en = $title;
        else
            $this->title = $title;
        return $this;
    }

    public function setTitleEn($title) {
        $this->title_en = $title;
        return $this;
    }

    public function getTitle() {
        if ($this->app->getLocalizationProvider()->getLocale() != 'ru')
            return $this->title_en;
        return $this->title;
    }

    public function setSportId($sportId) {
        $this->sport_id = $sportId;
        return $this;
    }

    public function getSportId() {
        return $this->sport_id;
    }

    public function getErrors() {
        $errors = [];
        if (!$this->title) {
            $errors[] = 'Введите название.';
        }
        if (!$this->title_en) {
            $errors[] = 'Введите название на английском.';
        }
        if (!$this->sport_id) {
            $errors[] = 'Выберите вид спорта.';
        }
        return $errors;
    }

    public function getEvents() {
        return $this->app->getObjectCache()->getEventWrapper()->findByField('tournament_id', $this->id)->execute();
    }

    public function getCompetitorsIds() {
        return $this->getWrapper()->getCompetitorsIds($this);
    }

}