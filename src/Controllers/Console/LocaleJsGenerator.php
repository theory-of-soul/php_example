<?php

namespace Controllers\Console;

use Models\Application;
use Models\Locale;   
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LocaleJsGenerator extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('locale:generate')->setDescription('Генерация locale.js из yml файлов локали');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $generator = new Locale($this->app);
        list($count, $fails, $log) = $generator->generate();
        if ($count) {
            $output->writeln("Locale built. $count items merged, $fails items skipped");
            if ($fails) {
                $output->writeln($log);
            }
        } else {
            $output->writeln('Locale build failed');
        }
    }
} 