<?php
/**
 * @author Manuele Menozzi <mmenozzi@webgriffe.com> 
 */

namespace Mmenozzi\WdTvLiveScraper\Service;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class VideoFinder
{
    public function findVideoFiles($directory, $excludeFilesWithMetadata = true)
    {
        $finder = $this->findVideoFilesInDirectory($directory);
        if ($excludeFilesWithMetadata) {
            $finder = $this->excludeFilesWithMetadata($finder);
        }
        return $finder;
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

    /**
     * @param Finder $finder
     * @return Finder
     */
    private function excludeFilesWithMetadata(Finder $finder)
    {
        $command = $this;
        $finder->filter(
            function (\SplFileInfo $file) use ($command) {
                if (file_exists($this->getMetadataFilePath($file))) {
                    return false;
                }
            }
        );
        return $finder;
    }

    public function getMetadataFilePath(SplFileInfo $file)
    {
        $filenameWithoutExtension = $this->getFilenameWithoutExtension($file);
        $filePath = $file->getPath();
        $metadataFilePathname = $filePath . DIRECTORY_SEPARATOR . $filenameWithoutExtension . '.xml';
        return $metadataFilePathname;
    }

    public function getMetathumbFilePath(SplFileInfo $file)
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
    private function getFilenameWithoutExtension(SplFileInfo $file)
    {
        $filenameWithoutExtension = $file->getBasename('.' . $file->getExtension());
        return $filenameWithoutExtension;
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
}