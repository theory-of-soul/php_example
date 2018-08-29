<?php

namespace Models;

class Sport extends AbstractModel {
	protected $id;
	protected $title;
	protected $slug;
        protected $keep;

    public function getWrapper() {
        return $this->app->getObjectCache()->getSportWrapper();
    }

	public function setId($id) {
        $this->id = $id;
        return $this;
    }

	public function getId() {
        return $this->id;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setSlug($slug) {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setKeep($keep) {
        $this->keep = $keep;
        return $this;
    }

    public function getKeep() {
        return $this->keep;
    }    
    public function getTournaments() {
        return $this->app->getObjectCache()->getTournamentWrapper()->findBySportId($this->get('id'));
    }

    public function getErrors() {
        $errors = [];
        if (!$this->title) {
            $errors[] = 'Введите название.';
        }
        if (!$this->slug) {
            $errors[] = 'Введите ключ.';
        }
        return $errors;
    }
}