<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelsIdReplacer extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('channelsid:replace')->setDescription('Изменение Id каналов (id захардкожены)');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $ids = [
            12349 => 99412,
            12351 => 99980,
            12350 => 99108,
            302 => 99102,
            7184 => 99103,
            8804 => 13022,
            12348 => 94020,
            12345 => 91236,
            105 => 99105,
            4214 => 94214,
            4208 => 94222,
            4513 => 94513,
            119 => 99119,
            4402 => 93505,
            13835 => 91239,
            13813 => 91233,
            12346 => 91232,
            86 => 99986,
            12342 => 99995,
            12343 => 99996,
            2003 => 92003,
            2140 => 99111,
            19108 => 19108,
            10310 => 91002,
            21103 => 91301,
            21104 => 91303,
            11109 => 91304,
            10102 => 91305,
            13103 => 91306,
            13104 => 91307,
            11110 => 91314,
            10101 => 99113,
            13101 => 91318,
            2202 => 12106,
            6060 => 99112,
        ];

        foreach($ids as $from => $to) {
            $channel = $this->app->getObjectCache()->getChannelWrapper()->findById($from);
            if(!$channel) {
                $output->writeln("channel not found: $from => $to");
                continue;
            }

            $alreadyExistsChannel = $this->app->getObjectCache()->getChannelWrapper()->findById($to);
            if($alreadyExistsChannel) {
                $output->writeln("channel already exists: $from => $to");
                continue;
            }

            foreach($channel->getEvents() as $event) {
                $event->setChannelId($to);
                $event->save();
            }

            $this->app->getMysql()->execute("UPDATE `channels` SET `id`=" . $to . " WHERE `id` = " . $from);
            $output->writeln("ok: $from => $to");
        }
    }
}