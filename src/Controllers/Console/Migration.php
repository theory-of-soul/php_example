<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migration extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('migration')->setDescription('Мигриграция БД');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $sql = "SELECT `file_number` FROM `migration_history` ORDER BY `file_number` DESC LIMIT 1";
        $result = $this->app->getMysql()->execute($sql)->fetch();

        $lastFileNumber = 0;
        if( isset($result['file_number']) ) {
            $lastFileNumber = $result['file_number'];
        }

        $path = ROOT . '/migrations';

        $migrations = [];
        $files = array_diff(scandir($path), ['.', '..', 'baseline.sql']);
        foreach($files as $file) {
            $fileNumber = str_replace('.sql', '', $file);
            if($fileNumber > $lastFileNumber) {
                $migrations[$fileNumber] = $file;
            }
        }

        ksort($migrations);

        foreach ($migrations as $i => $migrationFile) {
            $sql = file_get_contents($path . '/' . $migrationFile);
            $this->app->getMysql()->execute($sql);

            $sql = "INSERT INTO `migration_history` (`file_number`, `date_apply`) VALUES ( '$i', NOW() )";
            $this->app->getMysql()->execute($sql);
        }

        $output->writeln(count($migrations) . " migrations applied.");
    }
}