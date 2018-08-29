<?php

namespace Controllers\Console;

use Models\Application;
use Models\Event;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EPG extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('EPG')->setDescription('Загрузка событий');
    }

    private function log(OutputInterface $output, $text) {
        $output->writeln("[" . date('d/m/Y H:i:s') . "] " . $text);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        // backup events table
        exec('mysqldump -h ' . $this->app['config']['mysql']['host'] . ' -u' . $this->app['config']['mysql']['user'] . ' -p' . $this->app['config']['mysql']['password']  . ' ' . $this->app['config']['mysql']['baseName'] . ' events > ' . $this->app['config']['dumpPath'] . '/' . date('Y-m-d_H:i:s') . '.sql');
        
        //updating status
        $data = simplexml_load_file($this->app['config']['api']['epg']);
        $epg2chan = [];
        if (!$data || !$data->channel) {
            $this->log($output, "Invalid xml data probably");
            return -1;
        }
        foreach ($data->channel as $channel) {
            $epg = intval($channel['id']);
            $ch = $this->app->getObjectCache()->getChannelWrapper()->findByField('epg', $epg)->first();
            if (!$ch) {
                continue;
            }
            $epg2chan[$epg] = $ch;
            $this->log($output, "Channel $epg mapped to local channel " . $ch->get('id'));
        }
        if (!count($epg2chan)) {
            $this->log($output, "No valid channels found");
            return -1;
        }
        $count = 0;
        foreach ($data->programme as $event) {
            $epg = intval($event['channel']);
            if (!array_key_exists($epg, $epg2chan))
                continue;
            if (!$event->title)
                continue;
            $ch_id = intval($epg2chan[$epg]->get('id'));
            $startTime = strtotime(strval($event['start']));
            if ($startTime < time())
                continue;
            $endTime = strtotime(strval($event['stop']));
            $q = sprintf("SELECT `id` FROM `events` WHERE `channel_id` = '%u' AND `start` > '%s' AND `start`< '%s'",
                $ch_id,
                date('c', $startTime),
                date('c', $endTime));
            $overlap = $this->app->getMysql()->execute($q)->fetchAll();
            if ($overlap) {
                $oid = join(',', array_map(function ($item) {
                    return $item['id'];
                }, $overlap));
                $this->log($output, "Event $oid overlaps with new event " . strval($event->title) . " on channel $ch_id, deleting");
                $this->app->getMysql()->execute(sprintf('DELETE FROM `events` WHERE `id` IN (%s)', $oid));
            }
            $ev = $this->app->getObjectCache()->getEventWrapper()->findByChannelAndStart($ch_id, $startTime);
            if (!$ev) {
                $ev = new Event($this->app);
            }
            $title = strval($event->title);
            $ev->setProperties([
                'channel_id' => $ch_id,
                'start' => date('Y-m-d H:i:s', $startTime),
                'title' => $title,
                'title_en' => $title,
                'duration' => ceil(($endTime - $startTime) / 60)
            ])->save();
            $count++;
        }
        $this->log($output, "Total $count events merged");
        return 0;
    }
} 