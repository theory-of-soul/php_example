<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompetitorTournamentFiller extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('competitor-tournament:fill')->setDescription('Заполняет таблицу competitor_tournament');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $sql = "SELECT competitors.tournament_id, competitors.id FROM `competitors` INNER JOIN tournaments ON competitors.tournament_id = tournaments.id";
        $this->app->getMysql()->execute($sql);

        foreach($this->app->getMysql()->execute($sql)->fetchAll() as $competitor) {
            if($competitor['tournament_id']) {
                $sql = "INSERT INTO `competitor_tournament` (`competitor_id`, `tournament_id`) VALUES (" . $competitor['id'] . ", " . $competitor['tournament_id'] . ")";
                $this->app->getMysql()->execute($sql);
                $output->writeln("competitor_id: " . $competitor['id'] . " tournament_id: " . $competitor['tournament_id']);
            }
        }
    }
}