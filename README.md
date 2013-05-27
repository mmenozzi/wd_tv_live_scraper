WD TV Live Scraper
==================

Command-line application that helps you to retrieve TV Shows and Movies meta-data informations for [WD TV Live](http://store.westerndigital.com/store/wdus/en_US/compare/ThemeID.21986300/parentCategoryID.13092400/categoryID.13742300) media player.

TV Shows's meta-data comes from [**thetvdb.com**](http://thetvdb.com/) online database.

Movies's meta-data comes from [**themoviedb.org**](http://www.themoviedb.org/) online database.

For any suggestion or bug, please, let me know through GitHub's issues.

Requirements
------------

* Unix based shell (BSD, GNU or Cygwin)
* PHP (http://php.net/)
* cURL


Installation
------------

	cd /some/dir/
	git clone git@github.com:mmenozzi/wd_tv_live_scraper.git .
	composer install
	ln -s /some/dir/bin/wd_tv_live_scraper /usr/local/bin/wd_tv_live_scraper

Usage
-----

### TV Shows

Command syntax:

	wd_tv_live_scraper tvdb [--language="..."] [--force] [--dry-run] directory
	
You need to have all your episodes of a given TV Show stored in a `directory`. When the command starts it asks for searching a TV Show, enter the title (or part of it) and select from the list. After that the command starts retrieving meta-data for video files found in `directory` that didn't have related meta-data `.xml` file. Use `language` option to get meta-data in the given language. If `force`option is set, meta-data retrivial is forced also for files that already have meta-data.

For more informations type:
	
	wd_tv_live_scraper tvdb --help


### Movies

Command syntax:

	wd_tv_live_scraper tmdb [--language="..."] [--force] [--dry-run] directory

`directory` should be the path where your movies are stored. For each video file, that didn't already have meta-data, in the `directory`, the command will ask you for a movie. Enter the title and select from list. Use `language` option to get data in the given language. If `force`option is set, meta-data retrivial is forced also for files that already have meta-data.

For more informations type
	
	wd_tv_live_scraper tmdb --help