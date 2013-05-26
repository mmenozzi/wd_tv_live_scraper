<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Model\WdTvLive;


use TMDB\Client;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlRoot;

/** @XmlRoot("details") */
class Movie
{
    const YOUTUBE_BASE_URL = 'http://www.youtube.com/watch?v=';

    private $genre;
    private $id;
    private $imdb_id;
    private $overview;
    private $year;
    private $runtime;
    private $title;
    private $cast;
    private $director;
    private $trailer;

    /**
     * @XmlList(inline = true, entry = "backdrop")
     */
    private $backdrops = array();

    public function __construct(\TMDB\structures\Movie $movie, Client $client, $language)
    {
        $casts = $client->info('movie', $movie->id, 'casts');

        $this->genre = new NoCdataString($movie->genres[0]->name);
        $this->id = new NoCdataString($movie->id);
        $this->imdb_id = new NoCdataString($movie->imdb_id);
        $this->overview = new NoCdataString($movie->overview);
        $this->year = new NoCdataString($movie->release_date);
        $this->runtime = $movie->runtime;
        $this->title = new NoCdataString($movie->title);
        $this->cast = new NoCdataString($this->buildCastString($casts->cast));
        $this->director = new NoCdataString($this->buildDirectorString($casts->crew));
        $this->trailer = new NoCdataString($this->getYouTubeTrailer($movie, $language));

        $this->backdrops = $this->getBackdrops($movie, $client);
    }

    private function buildCastString(array $casts)
    {
        $castArray = array();
        foreach ($casts as $cast) {
            $castArray[] = $cast->name;
        }

        return implode(' / ', $castArray);
    }

    private function buildDirectorString(array $crews)
    {
        $directors = array();
        foreach ($crews as $crew) {
            if (!strcmp($crew->job, 'Director')) {
                $directors[] = $crew->name;
            }
        }

        return implode(' / ', $directors);
    }

    /**
     * @param \TMDB\structures\Movie $movie
     * @param $language
     * @return string
     */
    private function getYouTubeTrailer(\TMDB\structures\Movie $movie, $language)
    {
        $trailers = $movie->trailers($language);
        if (isset($trailers->youtube) && count($trailers->youtube) > 0) {
            return self::YOUTUBE_BASE_URL . $trailers->youtube[0]->source;
        }

        return '';
    }

    /**
     * @param \TMDB\structures\Movie $movie
     * @param Client $client
     * @return array
     */
    private function getBackdrops(\TMDB\structures\Movie $movie, Client $client)
    {
        $backdrops = array();
        $images = $client->info(
            'movie',
            $movie->id,
            'images',
            array('language' => '')
        );
        foreach ($images->backdrops as $backdrop) {
            $backdrops[] = new NoCdataString($client->image_url('backdrop', '1280', $backdrop->file_path));
        }
        return $backdrops;
    }
}
