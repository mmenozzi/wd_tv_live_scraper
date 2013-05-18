<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Command;


use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Mmenozzi\WdTvLiveScraper\Exception\NotValidSerieEpisodeFilenameException;
use Mmenozzi\WdTvLiveScraper\Model\WdTvLive\TvShowEpisode;
use Mmenozzi\WdTvLiveScraper\Serializer\NoCdataXmlHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TvDb\Client;
use TvDb\Serie;

class TvDbCommand extends Command
{
    const TVDB_BASE_URL = 'http://thetvdb.com/';
    const TVDB_API_KEY = 'D6ABE80AEE95DD32';
    const TVDB_BANNERS_BASE_URL = 'http://thetvdb.com/banners/';

    const DEFAULT_LANGUAGE = 'en';

    /**
     * @var $tvDbClient Client
     */
    private $tvDbClient;

    protected function configure()
    {
        $this
            ->setName('tvdb')
            ->setDescription('Retrieves meta-data for a TV Show in a given folder.')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Path to the directory where your serie\'s episodes are stored.'
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Language to use when searching on thetvdb.com (en, it, de, fr, ecc...). Default: %s.',
                    self::DEFAULT_LANGUAGE
                )
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Does this option needs a description?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');
        $language = $input->getOption('language');
        $dryRun = $input->getOption('dry-run');
        $language = $language ? $language : self::DEFAULT_LANGUAGE;

        $this->tvDbClient = new Client(self::TVDB_BASE_URL, self::TVDB_API_KEY);

        $dialog = $this->getHelperSet()->get('dialog');
        /** @var $dialog DialogHelper */

        $serie = $this->askForSerie($output, $dialog, $language);

        $finder = $this->findVideoFilesWithoutMetadata($directory);
        foreach ($finder as $file) {
            /** @var $file SplFileInfo */
            list($seasonNumber, $episodeNumber) = $this->getSeasonAndEpisodeNumbers($file);
            $output->writeln(
                sprintf(
                    'Retrieving meta-data for <info>%s</info> - [Season: <info>%s</info>. Episode: <info>%s</info>]...',
                    $file->getBasename(),
                    $seasonNumber,
                    $episodeNumber
                )
            );

            $metadataFilePath = $this->getMetadataFilePath($file);
            $episode = $this->tvDbClient->getEpisode($serie->id, $seasonNumber, $episodeNumber, $language);
            $banners = $this->tvDbClient->getBanners($serie->id);
            $wdTvLiveEpisode = new TvShowEpisode($episode, $serie, $banners);
            $serializer = SerializerBuilder::create()
                ->configureHandlers(
                    function(HandlerRegistry $registry) {
                        $registry->registerSubscribingHandler(new NoCdataXmlHandler());
                    }
                )
                ->build();

            $wdTvLiveEpisodeXml = $serializer->serialize($wdTvLiveEpisode, 'xml');
            if (!$dryRun) {
                file_put_contents($metadataFilePath, $wdTvLiveEpisodeXml);
            }

            $metathumbFilePath = $this->getMetathumbFilePath($file);
            if (!$dryRun) {
                file_put_contents($metathumbFilePath, file_get_contents($this->getSeriePosterUrl($serie)));
            }
        }

        $output->writeln('We\'re done!');
    }

    private function getSeriePosterUrl(Serie $serie)
    {
        return self::TVDB_BANNERS_BASE_URL . $serie->poster;
    }

    private function getSerieTeaser(Serie $serie)
    {
        return substr($serie->overview, 0, 60) . '...';
    }

    /**
     * @param OutputInterface $output
     * @param DialogHelper $dialog
     * @param Client $tvDbClient
     * @param $language
     * @return Serie
     */
    private function askForSerie(OutputInterface $output, DialogHelper $dialog, $language)
    {
        $searchTitle = $dialog->ask($output, 'Search TV Show: ');
        $output->writeln('Searching... Please wait...');

        $series = $this->tvDbClient->getSeries($searchTitle, $language);
        $choices = array();
        foreach ($series as $serie) {
            /** @var $serie Serie */
            $choices[] = sprintf(
                "\t%s - %s - %s\r\n\t%s\r\n",
                $serie->name,
                $serie->network,
                $this->getSeriePosterUrl($serie),
                $this->getSerieTeaser($serie)
            );
        }

        $choices[] = 'Repeat search';

        $choice = $dialog->select($output, 'Select:', $choices);

        if ($choice == count($series)) {
            return $this->askForSerie($output, $dialog, $language);
        }

        return $this->tvDbClient->getSerie($series[$choice]->id, $language);
    }

    /**
     * @param $directory
     * @return Finder
     */
    private function findVideoFilesInDirectory($directory)
    {
        $finder = Finder::create();
        $finder->in($directory)->files();
        foreach (self::getVideoFilePatterns() as $pattern) {
            $finder->name($pattern);
        }
        $finder->sortByName();
        return $finder;
    }

    private static function getVideoFilePatterns()
    {
        return array(
            '*.avi',
            '*.flv',
            '*.mov',
            '*.mp4',
            '*.mpg',
            '*.rm',
            '*.wmv',
            '*.vob',
            '*.mkv',
        );
    }

    /**
     * @param Finder $finder
     * @return Finder
     */
    private function excludeFilesWithMetadata(Finder $finder)
    {
        $command = $this;
        $finder->filter(
            function (\SplFileInfo $file) use ($command) {
                if (file_exists($command->getMetadataFilePath($file))) {
                    return false;
                }
            }
        );
        return $finder;
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    function getMetadataFilePath(\SplFileInfo $file)
    {
        $filenameWithoutExtension = $this->getFilenameWithoutExtension($file);
        $filePath = $file->getPath();
        $metadataFilePathname = $filePath . DIRECTORY_SEPARATOR . $filenameWithoutExtension . '.xml';
        return $metadataFilePathname;
    }

    private function findVideoFilesWithoutMetadata($directory)
    {
        $finder = $this->findVideoFilesInDirectory($directory);
        $finder = $this->excludeFilesWithMetadata($finder);
        return $finder;
    }

    /**
     * @param $file
     * @return array
     * @throws \Mmenozzi\WdTvLiveScraper\Exception\NotValidSerieEpisodeFilenameException
     */
    private function getSeasonAndEpisodeNumbers($file)
    {
        $matches = array();
        $regExp = '/(\d+)x(\d+)|s(\d)+e(\d)+/i';
        preg_match($regExp, $file->getBasename(), $matches);
        if (count($matches) > 3) {
            throw new NotValidSerieEpisodeFilenameException(
                sprintf(
                    'The file %s has an invalid file name. File name should match the following pattern: %s',
                    $file->getRealPath(),
                    $regExp
                )
            );
        }
        $season = (int)$matches[1];
        $episode = (int)$matches[2];
        return array($season, $episode);
    }

    private function getMetathumbFilePath($file)
    {
        $filenameWithoutExtension = $this->getFilenameWithoutExtension($file);
        $filePath = $file->getPath();
        $metathumbFilePathname = $filePath . DIRECTORY_SEPARATOR . $filenameWithoutExtension . '.metathumb';
        return $metathumbFilePathname;
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    private function getFilenameWithoutExtension(\SplFileInfo $file)
    {
        $filenameWithoutExtension = $file->getBasename('.' . $file->getExtension());
        return $filenameWithoutExtension;
    }
}
