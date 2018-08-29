<?php

namespace Controllers\Console;

use Models\Application;
use Models\Channel;
use Providers\Localization\LocalizationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Rotate extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('rotate')->setDescription('Обновление статуса событий и чистка базы');
    }

    private function log(OutputInterface $output, $text) {
        $output->writeln("[" . date('d/m/Y H:i:s') . "] " . $text);
        return -1;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        //clearing old events
        $result = $this->app->getObjectCache()->getEventWrapper()->clearOldEvents(48);
        $this->log($output, "$result events expired");

        //updating status
        $data = file_get_contents($this->app['config']['api']['stat']);
        
        if (!strlen($data))
            return $this->log($output, "STAT api offline");
        $data = json_decode($data, true);
        if (!is_array($data))
            return $this->log($output, "STAT api return non-parsable string");
        
        $channels = [0];
        $enabled = 0;
        
        //$this->app->getMysql()->execute("UPDATE channels SET `enabled`=0");
        foreach ($data as $channel) {

            $id = intval($channel['id']);
            if (!$id)
                continue;
            
            
            
            if($channel['bw']>0) $channels[] = $id;
            /**
             * @var Channel $ch
             */
            $ch = $this->app->getObjectCache()->getChannelWrapper()->findById($id);
            if (!is_object($ch)) {
                $this->log($output, "Channel not found, adding: $id");
                /*
                $ch = new Channel($this->app);
                $ch->setProperties([
                    'id' => $id,
                    'title' => $channel['name'],
                    'language' => LocalizationProvider::DEFAULT_LOCALE
                ]);
                 */
                if(!trim($channel['name'])) $channel['name'] = 'UNNAMED';
                $this->app->getMysql()->execute('INSERT INTO channels (id,title,enabled,language,epg) '.
                        'VALUES ('.$id.',"'.$channel['name'].'",'.($channel['bw']>0 ? 1 : 0).',"'.LocalizationProvider::DEFAULT_LOCALE.'",'.intval($channel['epg']).')');
            }
            else{
                if(trim($channel['name']))
                {
                    $ch->setProperties([
                        'epg' => intval($channel['epg']),
                        'title' => $channel['name'],
                        'enabled' => $channel['bw']>0 ? 1 : 0
                    ])->save();
                }
                else
                {
                    $ch->setProperties([
                        'epg' => intval($channel['epg']),
                        'enabled' => $channel['bw']>0 ? 1 : 0
                    ])->save();
                }
            }
            $enabled++;
        }
        $this->log($output, "$enabled channels online");
        $this->app->getMysql()->execute("UPDATE channels SET `enabled`=0 WHERE id NOT IN (".implode(',',$channels).")");


        $this->app->getMysql()->execute("DELETE FROM `news` WHERE `date`<DATE_SUB(NOW(),INTERVAL 2 MONTH)");
        $this->app->getMysql()->execute("DELETE FROM `reviews` WHERE `date`<DATE_SUB(NOW(),INTERVAL 2 MONTH)");
        $this->app->getMysql()->execute("DELETE FROM `comments` WHERE `rel`='event' AND NOT EXISTS(SELECT `events`.`id` FROM `events` WHERE `events`.`id`=`comments`.`rel_id`)");
        $this->app->getMysql()->execute("DELETE FROM `comments` WHERE `rel`='review' AND NOT EXISTS(SELECT `reviews`.`id` FROM `reviews` WHERE `reviews`.`id`=`comments`.`rel_id`)");
        $this->app->getMysql()->execute("DELETE FROM `comments` WHERE `rel`='news' AND NOT EXISTS(SELECT `news`.`id` FROM `news` WHERE `news`.`id`=`comments`.`rel_id`)");
        return 0;
    }
} 