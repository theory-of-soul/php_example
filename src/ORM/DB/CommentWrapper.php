<?php

namespace ORM\DB;

use Models\Comment;
use Providers\Mysql\FieldType\AbstractFieldType;


class CommentWrapper extends AbstractWrapper {
	public function getTableName() {
        return 'comments';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'rel' => ['method' => 'setRel'],
            'rel_id' => ['method' => 'setRelId', 'type' => AbstractFieldType::TYPE_INT],
            'date' => ['method' => 'setDate'],
            'user_id' => ['method' => 'setUserId', 'type' => AbstractFieldType::TYPE_INT],
            'text' => ['method' => 'setText'],
            'approved' => ['method' => 'setApproved', 'type' => AbstractFieldType::TYPE_INT],
        ];
    }

    protected function factoryObject($row = null) {
        return new Comment($this->app);
    }

    public function getByRel($relId, $rel) {
        return $this->select( $this->getAllFields() )
        ->where('rel', '=', $rel)
        ->andWhere('rel_id', '=', $relId)
        ->andWhere('approved', '=', 1)
        ->execute();
    }

    public function getCommentsData($relId, $rel) {
        $rel = preg_replace('/^[^\w^\d]+$/', '', $rel);
        if(!preg_match('/^[\w]+$/', $rel)) {
            return [];
        }

        $sql = 'SELECT `comments`.*, `user`.`username`, `user`.`firstname`, `user`.`lastname` 
        FROM `comments` INNER JOIN `user` ON `comments`.`user_id`=`user`.`id`
        WHERE `comments`.`approved`=1 AND `comments`.`rel`="' . $rel .'" AND `comments`.`rel_id`=' . (int)$relId;
        return $this->app->getMysql()->fetchAll($sql);
    }

    public function getAllWithUserData() {
        $sql = 'SELECT `comments`.*, `user`.`username`, `user`.`firstname`, `user`.`lastname` 
        FROM `comments` INNER JOIN `user` ON `comments`.`user_id`=`user`.`id`';
        return $this->app->getMysql()->fetchAll($sql);
    }

    public function postComment($data) {
        $comment = new Comment($this->app);
        $comment->setProperties(array_merge($data, [
            'date' => date('Y-m-d H:i:s'),
            'approved' => 1 //COMMENT PRE-MODERATION DISABLE 11.11.2016
        ]));
        $comment->save();
    }
}