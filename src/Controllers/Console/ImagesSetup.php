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

class ImagesSetup extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('images')->setDescription('Поиск недостающих изображений');
    }

    private function log(OutputInterface $output, $text) {
        $output->writeln("[" . date('d/m/Y H:i:s') . "] " . $text);
        return -1;
    }

    public function import() {
        $db = $this->app->getMysql();
        $sourceFile = './teams_icon.csv';
        $imageDir = './logo_teams/';
        ob_start();
        mb_internal_encoding("UTF-8");
        ini_set("default_charset", "UTF-8");
        setlocale(LC_ALL, 'ru_RU.utf8');
        $f = fopen($sourceFile, 'rb');
        if (!$f) {
            throw new Exception("can't open file");
        }

        $s = $this->app->getObjectCache()->getSportWrapper()->getAll();
        $sp = [];
        foreach ($s as $sport)
            $sp[mb_strtolower($sport->get('title'), 'UTF-8')] = [
                'id' => $sport->get('id'),
                'slug' => $sport->get('slug'),
                'teams' => null
            ];
        unset($s);
        foreach ($sp as $key => $sport) {
            $t = $this->app->getObjectCache()->getTournamentWrapper()->findBySportId($sport['id']);
            if (!$t || !count($t)) {
                unset($sp[$key]);
                continue;
            }
            $tids = array_pluck($t, 'id');
            $c = [];
            $cmp = $db->execute("SELECT `id`,`title`,`img` FROM competitors where tournament_id IN (" . join(',', $tids) . ")")->fetchAll();
            foreach ($cmp as $cm)
                $c[mb_strtolower($cm['title'], 'UTF-8')] = [
                    'img' => $cm['img'],
                    'id' => $cm['id']
                ];

            $sp[$key]['teams'] = $c;
        }
        unset($c);
        unset($cmp);

        $counter = 0;
        while (!feof($f)) {
            $line = fgetcsv($f, null, ';', '"');
            if (!$line || !$line[0])
                continue;
            list($file, $team, $sport) = array_map(function ($item) {
                return mb_strtolower(trim($item), 'UTF-8');

            }, $line);
            if (!array_key_exists($sport, $sp)) {
                continue;
            }
            if (!array_key_exists($team, $sp[$sport]['teams']))
                continue;
            $img = $imageDir . $file . '.png';
            $subdir = $imageDir . $sp[$sport]['slug'];
            if (!file_exists($img))
                continue;
            if (!is_dir($subdir))
                mkdir($subdir);
            rename($img, $imageDir . $sp[$sport]['teams'][$team]['img']);
            echo $sp[$sport]['teams'][$team]['img'], ' mapped to ', $img, "\n";
            $counter++;
        }
        echo "$counter files found\n";

        return ob_get_clean();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!($log = $this->import()))
            return -1;
        $this->log($output, $log);
        return 0;
    }
} 