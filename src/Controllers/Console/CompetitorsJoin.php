<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompetitorsJoin extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('competitors:join')->setDescription('Объединяет дублирующие команды по названиям');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $competitors = [];
        foreach($this->app->getObjectCache()->getCompetitorWrapper()->getAll() as $competitor) {
            $competitors[ $competitor->get('id') ] = $competitor;
        }
        
        while(count($competitors)) {
            foreach($competitors as $competitor) {
                
                $result = array_filter($competitors, function($item) use ($competitor) {
                    return $item->get('title') == $competitor->get('title') && $item->get('title_en') == $competitor->get('title_en');
                });

                foreach($result as $item) {
                    unset($competitors[ $item->get('id') ]);
                }

                if(count($result) > 1) {
                    $this->join($result);
                    break;
                }
            }
        }
    }

    private function join($competitors) {
        $firstCompetitor = reset($competitors);
        unset($competitors[ key($competitors) ]);

        echo "\nremined: " . $firstCompetitor->get('id') . ' - ' . $firstCompetitor->get('title') . ' - ' . $firstCompetitor->get('title_en') . "\n";
        foreach($competitors as $item) {
            $this->joinNews($item, $firstCompetitor);
            $this->joinReviews($item, $firstCompetitor);
            $this->joinUsers($item, $firstCompetitor);

            $sql = 'UPDATE events SET home = ' . $firstCompetitor->get('id') . ' WHERE home = ' . $item->get('id');
            $this->app->getMysql()->execute($sql);
            $sql = 'UPDATE events SET away = ' . $firstCompetitor->get('id') . ' WHERE away = ' . $item->get('id');
            $this->app->getMysql()->execute($sql);

            $sql = 'UPDATE competitor_tournament SET competitor_id = ' . $firstCompetitor->get('id') . ' WHERE competitor_id = ' . $item->get('id');
            $this->app->getMysql()->execute($sql);

            $item->delete();
            echo 'removed: ' . $item->get('id') . "\n";
        }
    }

    private function joinNews($fromCompetitor, $toCompetitor) {
        foreach($this->app->getObjectCache()->getNewsWrapper()->getAll() as $item) {
            $ids = $item->getCompetitorsId();
            if( in_array($fromCompetitor->get('id'), $ids) ) {
                foreach($ids as $i => $id) {
                    if($id == $fromCompetitor->get('id')) {
                        $ids[$i] = $toCompetitor->get('id');
                    }
                }

                $item->setCompetitorsId($ids);
                echo "news id: " . $item->get('id') . " updated\n";
                $item->save();
            }
        }
    }

    private function joinReviews($fromCompetitor, $toCompetitor) {
        foreach($this->app->getObjectCache()->getReviewsWrapper()->getAll() as $item) {
            $ids = $item->getCompetitorsId();
            if( in_array($fromCompetitor->get('id'), $ids) ) {
                foreach($ids as $i => $id) {
                    if($id == $fromCompetitor->get('id')) {
                        $ids[$i] = $toCompetitor->get('id');
                    }
                }

                $item->setCompetitorsId($ids);
                echo "review id: " . $item->get('id') . " updated\n";
                $item->save();
            }
        }
    }

    private function joinUsers($fromCompetitor, $toCompetitor) {
        foreach($this->app->getObjectCache()->getUserWrapper()->getAll() as $item) {
            $ids = $item->getTeamIds();
            if( in_array($fromCompetitor->get('id'), $ids) ) {
                foreach($ids as $i => $id) {
                    if($id == $fromCompetitor->get('id')) {
                        $ids[$i] = $toCompetitor->get('id');
                    }
                }

                $item->setTeamIds($ids);
                echo "user id: " . $item->get('id') . " updated\n";
                $item->save();
            }
        }
    }
}