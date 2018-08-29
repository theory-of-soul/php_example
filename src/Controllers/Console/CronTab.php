<?php

namespace Controllers\Console;

use Models\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Yaml\Parser;


class CronTab extends Command {
    protected $app;

    public function __construct(Application $app, $name = null) {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure() {
        $this->setName('crontab')->setDescription('Формирование задач крона по конфигурационному файлу')
        ->addArgument('logDirectory', InputArgument::REQUIRED)
        ->addArgument('rootDirectory', InputArgument::REQUIRED)
        ->addArgument('solution', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $yml = new Parser();

        $logDirectory = $input->getArgument('logDirectory');
        $rootDirectory = $input->getArgument('rootDirectory');
        $solution = $input->getArgument('solution');

        $cronYmlCommon = $yml->parse(file_get_contents(__DIR__ . '/../../../crontab/crontab_common.yml'));
        $cronYmlSolution = $yml->parse(file_get_contents(__DIR__ . '/../../../crontab/crontab_' . $solution . '.yml'));
        if ($cronYmlCommon === NULL) {
            $cronYmlCommon = [];
        }
        if ($cronYmlSolution === NULL) {
            $cronYmlSolution = [];
        }
        $cronYml = array_merge($cronYmlCommon, $cronYmlSolution);
        $cronTab = '';
        foreach ($cronYml as $key => $value) {
            $cronLine = [];

            if (!array_key_exists('script', $value)) {
                throw new \Exception('Unknown script');
            }

            if (array_key_exists('minute', $value)) {
                $cronLine[] = $value['minute'];
            } else {
                $cronLine[] = '*';
            }

            if (array_key_exists('hour', $value)) {
                $cronLine[] = $value['hour'];
            } else {
                $cronLine[] = '*';
            }

            if (array_key_exists('day_of_month', $value)) {
                $cronLine[] = $value['day_of_month'];
            } else {
                $cronLine[] = '*';
            }

            if (array_key_exists('month', $value)) {
                $cronLine[] = $value['month'];
            } else {
                $cronLine[] = '*';
            }

            if (array_key_exists('day_of_week', $value)) {
                $cronLine[] = $value['day_of_week'];
            } else {
                $cronLine[] = '*';
            }

            $cronLine[] = str_replace('%root%', $rootDirectory, $value['script']);

            $cronLine[] = '>>' . $logDirectory . '/cron.' . $key . '.log';
            $cronLine[] = '2>>' . $logDirectory . '/cron.' . $key . '.error.log';
            $cronTab .= implode("\t", $cronLine) . "\n";
        }

        file_put_contents(__DIR__ . '/../../../crontab/crontab.txt', $cronTab);
    }
} 