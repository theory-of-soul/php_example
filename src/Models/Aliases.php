<?php

namespace Models;

class Aliases extends AbstractModel {
    protected $id;
    protected $title;
    protected $lang;
    protected $tournament_id;

    public function getWrapper() {
        return $this->app->getObjectCache()->getAliasesWrapper();
    }

    public function beforeSave() {
        $this->title = (string)$this->title;
        $this->tournament_id = (int)$this->tournament_id;
        $this->lang = (string)$this->lang;
        $this->tournament_id = (int)$this->tournament_id;
    }

    public function getTournamentId() {
        return $this->tournament_id;
    }

    public function getErrors() {
        $errors = [];
        if(!$this->title) {
            $errors[] = 'Введите название алиаса.';
        }
        return $errors;
    }


}