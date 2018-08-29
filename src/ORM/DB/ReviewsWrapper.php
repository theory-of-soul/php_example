<?php

namespace ORM\DB;

use Models\Reviews;
use Providers\Mysql\FieldType\AbstractFieldType;


class ReviewsWrapper extends AbstractWrapper {
	public function getTableName() {
        return 'reviews';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'sport_id' => ['method' => 'setSportId', 'type' => AbstractFieldType::TYPE_INT],
            'tournament_id' => ['method' => 'setTournamentId', 'type' => AbstractFieldType::TYPE_INT],
            'title' => ['method' => 'setTitle'],
            'text' => ['method' => 'setText'],
            'file' => ['method' => 'setFile'],
            'date' => ['method' => 'setDate'],
            'preview' => ['method' => 'setPreview'],
            'locale' => ['method' => 'setLocale'],
            'competitors_id' => ['method' => 'setCompetitorsId'],
            'notify' => ['method' => 'setNotify', 'type' => AbstractFieldType::TYPE_INT],
            'counter' => ['method' => 'setCounter', 'type' => AbstractFieldType::TYPE_INT],
            'url' => ['method' => 'setUrl'],
        ];
    }

    protected function factoryObject($row = null) {
        return new Reviews($this->app);
    }

    public function getByLocale() {
        $locale = $this->app->getLocalizationProvider()->getLocale();
        return $this->findByField('locale', $locale)->execute();
    }

    public function notify($id) {
        $review = $this->findById($id);
        if(!$review) {
            return ['success' => false, 'errors' => ["Обзор с Id $id не найден"]];
        }

        if(!count($review->getCompetitorsId())) {
            return ['success' => false, 'errors' => ["Рассылка не сделана, не найдено тегов для обзора"]];
        }

        $usersCount = $review->notifyUsers();

        if($usersCount) {
            $review->setProperties(['notify' => 1]);
            $review->save();
        }

        return ['success' => true, 'usersCount' => $usersCount];
    }

    public function increaseCounter($review) {
        $mysql = $this->app->getMysql();

        $mysql->startTransaction();
        $result = $mysql->execute('SELECT `counter` FROM `reviews` WHERE `id` = ' . $review->get('id'))->fetch();
        $counter = $result['counter'];
        $mysql->execute('UPDATE `reviews` SET `counter` = ' . ++$counter . ' WHERE `id` = ' . $review->get('id'));
        $mysql->commit();
    }
}