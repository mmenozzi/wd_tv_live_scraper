<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Serializer;


use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\XmlSerializationVisitor;
use Mmenozzi\WdTvLiveScraper\Model\WdTvLive\NoCdataString;

class NoCdataXmlHandler implements SubscribingHandlerInterface
{
    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'xml',
                'type' => 'Mmenozzi\WdTvLiveScraper\Model\WdTvLive\NoCdataString',
                'method' => 'serializeNoCdataStringToXml',
            ),
        );
    }

    public function serializeNoCdataStringToXml(
        XmlSerializationVisitor $visitor,
        NoCdataString $string,
        array $type
    ) {
        return $visitor->document->createTextNode((string)$string);
    }
}