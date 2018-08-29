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

class CompetitorsCache extends Command {
    const JS_LOCALE_FILE = ROOT . '/web/assets/js/competitors.js';

    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('competitors-cache-update')->setDescription('Обновление кеша команд');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $items = [];
        $competitors = $this->app->getObjectCache()->getCompetitorWrapper()->getAll();
        foreach ($competitors as $competitor) {
            $items[] = $competitor->getProperties();
        }

        file_put_contents(self::JS_LOCALE_FILE, 'var COMPETITORS = ' . json_encode($items, JSON_UNESCAPED_UNICODE));
        $output->writeln(count($items) . ' команд в кеше.');
    }
} 