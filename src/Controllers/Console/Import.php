<?php

namespace Controllers\Console;

use Models\Application;
use Models\Channel;
use Models\Competitor;
use Models\Tournament;
use Providers\Localization\LocalizationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

class Import extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('import')->setDescription('Загрузка турниров и команд')->addArgument('filename', InputArgument::REQUIRED);
    }

    private function log(OutputInterface $output, $text) {
        $output->writeln("[" . date('d/m/Y H:i:s') . "] " . $text);
        return -1;
    }

    public function import($filename) {
        $db = $this->app->getMysql();
        ob_start();
        mb_internal_encoding("UTF-8");
        ini_set("default_charset", "UTF-8");
        setlocale(LC_ALL, 'ru_RU.utf8');
        $f = fopen($filename, 'rb');
        if (!$f) {
            throw new Exception("can't open file");
        }

        $db->execute("SET NAMES 'UTF8'");
        $db->execute("TRUNCATE tournaments");
        $db->execute("TRUNCATE competitors");


        $sports = [];
        $slugs=[];
        foreach ($this->app->getObjectCache()->getSportWrapper()->getAll() as $sport) {
            $slugs[$sport->get('id')]=$sport->get('slug');
            $sports[$sport->get('id')] = mb_strtolower($sport->get('title'), 'UTF-8');
        }
        $sport = null;
        $tournament = null;
        $tournaments = [];
        $teams = 0;
        $lNum = 0;
        while (!feof($f)) {
            $lNum++;
            $line = array_map('trim', explode(',', fgets($f)));
            if (implode('', $line) == '')
                continue;
            if (!$line[0] && !$sport)
                continue;
            if ($line[0])
                $sport = array_search(mb_strtolower($line[0], 'UTF-8'), $sports);
            if ($sport === false) {
                echo "Sport not found: {$line[0]} on line $lNum\n";
                continue;
            }
            if ($line[1]) {
                if (in_array($line[1], $tournaments))
                    $tournament = array_search($line[1], $tournaments);
                else {
                    $t = new Tournament($this->app);
                    $t->setProperties(
                        ['title' => $line[1],
                            'title_en' => $line[3],
                            'sport_id' => $sport
                        ]);
                    $t->save();
                    $tournament = $db->getLastId();
                    $tournaments[$tournament] = $line[1];
                }
            }
            if (!$tournament && !$line[1]) {
                echo "Undefinied tournament on line $lNum\n";
                continue;
            }
            if (!$line[2] || !$line[4]) {
                echo "Undefinied team name on line $lNum\n";
                continue;
            }
            $teams++;
            $c = new Competitor($this->app);
            $c->setProperties([
                'title' => $line[2],
                'title_en' => $line[4],
                'tournament_id' => $tournament,
                'img' => $slugs[$sport] . '/' . $line[4] . '.png'
            ]);
            $c->save();
        }

        echo "Total $teams teams, ", count($tournaments), " tournaments\n";

        // Удаляем из событий ссылки на несуществующие турниры и команды

        $tids = array_map(function ($item) {
            return intval($item['id']);
        }, $db->execute("SELECT events.id
                      FROM events
                      WHERE tournament_id AND NOT exists(SELECT id
                      FROM tournaments
                      WHERE id = events.tournament_id)")->fetchAll()
        );
        if (count($tids)) {
            $numRows = $db->execute("UPDATE events set tournament_id=0 WHERE id IN (" . implode(',', $tids) . ")")->rowCount();

            echo $numRows, " events were cleaned up from nonexistent tournaments\n";
        }

        $cids = array_map(function ($item) {
            return intval($item['id']);
        }, $db->execute("SELECT events.id
                      FROM events
                      WHERE (home or away) AND NOT exists(SELECT id
                      FROM competitors
                      WHERE id = events.home or id=events.away)")->fetchAll()
        );
        if (count($cids)) {
            $numRows = $db->execute("UPDATE events set home=0,away=0 WHERE id IN (" . implode(',', $cids) . ")")->rowCount();
            echo $numRows, " events were cleaned up from nonexistent competitors\n";
        }
        return ob_get_clean();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!($log = $this->import($input->getArgument('filename'))))
            return -1;
        $this->log($output, $log);
        return 0;
    }
} 