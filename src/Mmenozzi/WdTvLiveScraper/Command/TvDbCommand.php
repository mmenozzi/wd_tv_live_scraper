<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TvDbCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tvdb')
            ->setDescription('Retrieves meta-data for a TV Serie in a given folder.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Ciao!');
    }
}
