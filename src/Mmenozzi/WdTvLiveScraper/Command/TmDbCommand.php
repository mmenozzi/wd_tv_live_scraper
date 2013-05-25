<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TmDbCommand extends Command
{
    const TMDB_API_KEY = 'db22855436b0520ea807bcafd6f4b6dc';
    const DEFAULT_LANGUAGE = 'en';

    protected function configure()
    {
        $this
            ->setName('tmdb')
            ->setDescription('Retrieves meta-data for Movies in a given folder.')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Path to the directory where your movies are stored.'
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Language to use when searching on themoviedb.org (en, it, de, fr, ecc...). Default: %s.',
                    self::DEFAULT_LANGUAGE
                )
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'This option needs a description?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello world');
    }
}
