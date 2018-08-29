<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Backup extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('backup')->setDescription('Бекап таблицы events');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        exec('mysqldump -h ' . $this->app['config']['mysql']['host'] . ' -u' . $this->app['config']['mysql']['user'] . ' -p' . $this->app['config']['mysql']['password']  . ' ' . $this->app['config']['mysql']['baseName'] . ' events > ' . $this->app['config']['dumpPath'] . '/' . date('Y-m-d_H:i:s') . '.sql');
    }
} 