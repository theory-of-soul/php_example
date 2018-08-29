<?php

namespace Models;

class Comment extends AbstractModel {
	protected $id;
	protected $rel;
    protected $rel_id;
    protected $date;
    protected $user_id;
    protected $text;
	protected $approved;

    public function getWrapper() {
        return $this->app->getObjectCache()->getCommentWrapper();
    }

    public function beforeSave() {
        $this->rel = (string)$this->rel;
        $this->rel_id = (int)$this->rel_id;
        $this->date = (string)$this->date;
        $this->user_id = (int)$this->user_id;
        $this->text = trim( strip_tags($this->text) );
        $this->approved = (int)$this->approved;
    }

    public function getErrors() {
        $errors = [];

        return $errors;
    }
}