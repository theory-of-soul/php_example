<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompetitorsCloner extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('competitors:clone')->setDescription('Клонирование компетиторов из одного турнира в другие')
        ->addArgument('idFrom', InputArgument::REQUIRED, 'ID турнира источника')
        ->addArgument('idsTo', InputArgument::REQUIRED, 'ID турниров в которые копируем команды, например: "212,2323,32,322"');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $tournament = $this->app->getObjectCache()->getTournamentWrapper()->findById( $input->getArgument('idFrom') );

        if(!$tournament) {
            $output->writeln('Tournament source with ID: ' . $input->getArgument('idFrom') . ' not found');
        } else {
            $ids = explode(',', $input->getArgument('idsTo'));
            foreach($ids as $id) {
                $item = $this->app->getObjectCache()->getTournamentWrapper()->findById( (int)$id );
                if($item) {
                    $this->cloneCompetitors($tournament, $item);
                } else {
                    $output->writeln('Tournament with ID: ' . (int)$id . ' not found');
                }
            }
        }
    }

    private function cloneCompetitors($tournamentSource, $tournamentTarget) {
        $oldCompetitorsIds = $tournamentTarget->getCompetitorsIds();
        foreach($tournamentSource->getCompetitorsIds() as $competitorId) {
            if(!in_array($competitorId, $oldCompetitorsIds)) {
                $sql = "insert into competitor_tournament (competitor_id, tournament_id) VALUES (" . $competitorId . ", " . $tournamentTarget->get('id') . ")";
                $this->app->getMysql()->execute($sql);
            }
        }
    }
} 