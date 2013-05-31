<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Command;


use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Mmenozzi\WdTvLiveScraper\Serializer\NoCdataXmlHandler;
use Mmenozzi\WdTvLiveScraper\Service\VideoFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use TMDB\Client;
use TMDB\structures\Movie;

class TmDbCommand extends Command
{
    const TMDB_API_KEY = 'db22855436b0520ea807bcafd6f4b6dc';
    const DEFAULT_LANGUAGE = 'en';

    /**
     * @var Client $tmDbClient
     */
    private $tmDbClient;

    /**
     * @var VideoFinder $videoFinder
     */
    private $videoFinder;

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
                'force',
                null,
                InputOption::VALUE_NONE,
                'If set, meta-data retrivial is forced also for files that already have meta-data.'
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
        $directory = $input->getArgument('directory');
        $language = $input->getOption('language') ? $input->getOption('language') : self::DEFAULT_LANGUAGE;
        $excludeFilesWithMetadata = !$input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $this->tmDbClient = Client::getInstance(self::TMDB_API_KEY);
        $this->tmDbClient->language = $language;
        $this->videoFinder = new VideoFinder();

        $finder = $this->videoFinder->findVideoFiles($directory, $excludeFilesWithMetadata);
        $totalFiles = count($finder);
        $current = 1;
        foreach ($finder as $file) {
            /** @var $file SplFileInfo */

            $movie = $this->askForMovie($output, $file, $language, $current, $totalFiles);
            $wdTvMovie = new \Mmenozzi\WdTvLiveScraper\Model\WdTvLive\Movie($movie, $this->tmDbClient, $language);

            $serializer = $this->initSerializer();
            $wdTvLiveMovieXml = $serializer->serialize($wdTvMovie, 'xml');

            $metadataFilePath = $this->videoFinder->getMetadataFilePath($file);
            $metathumbFilePath = $this->videoFinder->getMetathumbFilePath($file);

            if (!$dryRun) {
                file_put_contents($metadataFilePath, $wdTvLiveMovieXml);
                file_put_contents($metathumbFilePath, file_get_contents($movie->poster('150')));
            }
            $output->writeln(
                sprintf(
                    '%sSaved meta-data and thumbnail for <info>%s</info>.',
                    $dryRun ? '[Dry Run] - ' : '',
                    $movie->title
                )
            );
            $output->writeln('');
            $current++;
        }
        $output->writeln('We\'re done!');
    }

    /**
     * @param OutputInterface $output
     * @param SplFileInfo $file
     * @param $language
     * @return Movie
     */
    private function askForMovie(OutputInterface $output, SplFileInfo $file, $language, $current, $total)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        /** @var $dialog DialogHelper */

        $searchTitle = $dialog->ask(
            $output,
            sprintf('[%s of %s] - Search movie for file <info>%s</info>: ', $current, $total, $file->getBasename())
        );
        $output->writeln('Searching... Please wait...');

        $results = $this->tmDbClient->search('movie', array('query' => $searchTitle, 'language' => $language));

        $choices = array();
        $movies = array();
        foreach ($results as $movie) {
            /** @var $movie Movie */

            $movie = new Movie($movie->id);
            $movies[] = $movie;
            $choices[] = sprintf(
                "\t%s (%s) - %s\r\n\t%s\r\n",
                $movie->title,
                $movie->release_date,
                $movie->poster('150'),
                substr($movie->overview, 0, 60) . '...'
            );
        }

        $choices[] = "\tRepeat search";

        $choice = $dialog->select($output, 'Select:', $choices);

        if ($choice == count($results)) {
            return $this->askForMovie($output, $file, $language, $current, $total);
        }

        return $movies[$choice];
    }

    /**
     * @return \JMS\Serializer\Serializer
     */
    private function initSerializer()
    {
        $serializer = SerializerBuilder::create()
            ->configureHandlers(
                function (HandlerRegistry $registry) {
                    $registry->registerSubscribingHandler(new NoCdataXmlHandler());
                }
            )
            ->build();
        return $serializer;
    }
}
