<?php

namespace ORM\DB;

use Models\Channel;
use Providers\Mysql\FieldType\AbstractFieldType;


class ChannelWrapper extends AbstractWrapper {
	public function getTableName() {
        return 'channels';
    }

    protected function getSchema() {
        return [
            'id' => ['method' => 'setId', 'type' => AbstractFieldType::TYPE_INT, 'primary' => true],
            'title' => ['method' => 'setTitle'],
            'description' => ['method' => 'setDescription'],
            'description_en' => ['method' => 'setDescriptionEn'],
            'enabled' => ['method' => 'setEnabled', 'type' => AbstractFieldType::TYPE_INT],
            'visible' => ['method' => 'setVisible', 'type' => AbstractFieldType::TYPE_INT],
            'language' => ['method' => 'setLanguage'],
            'uptime' => ['method' => 'setUptime'],
            'epg' => ['method' => 'setEpg'],
        ];
    }

    protected function factoryObject($row = null) {
        return new Channel($this->app);
    }

    public function save($data) {
        if(empty($data['id']) || !preg_match('/^\d+$/', $data['id'])) {
            return ['success' => false, 'errors' => ['can not save, id is empty or not number']];
        }

        $item = $this->select( $this->getAllFields() )->where('id', '=', $data['id'])->first();

        if(!$item) {
            $item = $this->factoryObject();
        }

        $item->setProperties($data);
        $errors = $item->getErrors();
        if(!$errors) {
            $item->save();
            $result = ['success' => true, 'id' => $item->get('id')];
        } else {
            $result = ['success' => false, 'errors' => $errors];
        }
        return $result;
    }

    public function getEvents($channel) {
        $eventWrapper = $this->app->getObjectCache()->getEventWrapper();
        return $eventWrapper->select( $eventWrapper->getAllFields() )->where('channel_id', '=', $channel->get('id'))->execute();
    }

    public function updateChannelsStatus($channels_up) {
        $sql = "UPDATE `channels` SET `enabled` = 0";
        $this->app->getMysql()->execute($sql);

        $channels_up = array_map(function($item) { 
            return (int)$item;
        }, $channels_up);

        $sql = "UPDATE `channels` SET `enabled` = 1 WHERE `id` IN (" . implode(',', $channels_up) . ")";
        $result = $this->app->getMysql()->execute($sql);
        return $result->rowCount();
    }

    public function getChannelsData($activeOnly = false, $withEvents = false, $onlyNotOverEvents = true) {
        $events = [];
        if($withEvents) {
            foreach($this->app->getObjectCache()->getEventWrapper()->getAll() as $event) {
                if( !isset($events[ $event->get('channel_id') ]) ) {
                    $events[ $event->get('channel_id') ] = [];
                }

                if(!$onlyNotOverEvents || $event->isNotOver()) {
                    $events[ $event->get('channel_id') ][] = $event->getProperties();
                }
            }
        }

        $channels = $this->getAll();
        $items = [];
        foreach($channels as $item) {
            if($activeOnly && !$item->get('enabled')) {
                continue;
            }

            $channelData = $item->getProperties();
            $channelData['events'] = [];

            if( isset($events[ $item->get('id') ]) ) {
                $channelData['events'] = $events[ $item->get('id') ];
            }

            $items[] = $channelData;
        }

        return $items;
    }
}