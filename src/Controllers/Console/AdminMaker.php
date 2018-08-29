<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AdminMaker extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('admin-maker')->setDescription('Создание админа')
        ->addArgument('username', InputArgument::REQUIRED)
        ->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $result = $this->app->getObjectCache()->getAdminWrapper()
        ->registration( $input->getArgument('username'), $input->getArgument('password') );
        
        if($result['success']) {
            $output->writeln('Новый админ создан');
        } else {
            $output->writeln($result['error']);
        }
    }
} 