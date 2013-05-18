<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Model\WdTvLive;

use Mmenozzi\WdTvLiveScraper\Command\TvDbCommand;
use TvDb\Banner;
use TvDb\Episode;
use TvDb\Serie;
use JMS\Serializer\Annotation\XmlRoot;
use JMS\Serializer\Annotation\XmlList;

/** @XmlRoot("details") */
class TvShowEpisode
{
    private $id;
    private $title;
    private $series_name;
    private $episode_name;
    private $season_number;
    private $episode_number;
    private $firstaired;
    private $genre;
    private $runtime;
    private $director;
    private $actor;
    private $overview;

    /**
     * @XmlList(inline = true, entry = "backdrop")
     */
    private $backdrops = array();

    public function __construct(Episode $episode, Serie $serie, array $banners)
    {
        $this->id = $episode->id;
        $this->title = new NoCdataString(
            sprintf(
            '%s Season %s - %s %s',
            $serie->name,
            $episode->season,
            $episode->number,
            $episode->name
            )
        );
        $this->series_name = new NoCdataString($serie->name);
        $this->episode_name = new NoCdataString($episode->name);
        $this->season_number = new NoCdataString($episode->season);
        $this->episode_number = new NoCdataString($episode->number);
        $this->firstaired = new NoCdataString($episode->firstAired->format('Y-m-d'));
        $this->genre = new NoCdataString(implode(', ', $serie->genres));
        $this->runtime = $serie->runtime;
        $this->director = new NoCdataString(implode(', ', $episode->directors));
        $this->actor = new NoCdataString(implode(', ', $serie->actors));
        $this->overview = new NoCdataString($episode->overview);

        foreach ($banners as $banner) {
            /** @var $banner Banner */
            if ($banner->type !== 'fanart') {
                continue;
            }

            $this->backdrops[] = new NoCdataString(TvDbCommand::TVDB_BANNERS_BASE_URL . $banner->path);
        }
    }
}
