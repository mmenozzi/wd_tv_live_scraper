<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Model\WdTvLive;


class NoCdataString
{
    private $string;

    public function __construct($string)
    {
        // TODO gestire stringhe che andrebbero per forza in CDATA
        $this->string = $string;
    }

    public function __toString()
    {
        return (string)$this->string;
    }
}