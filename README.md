WD TV Live Scraper
==================

Command-line application that helps you to retrieve TV Shows's meta-data informations for [WD TV Live](http://store.westerndigital.com/store/wdus/en_US/compare/ThemeID.21986300/parentCategoryID.13092400/categoryID.13742300) media player.

TV Shows's meta-data comes from [**thetvdb.com**](http://thetvdb.com/) online database.

For any suggestion, please, let me know through GitHub's issues.

Requirements
------------

* Unix based operating system
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

	wd_tv_live_scraper tvdb [--language="..."] [--dry-run] directory

For more infor type:
	
	wd_tv_live_scraper tvdb --help
	
You need to have all your episodes of a given TV Show stored in a `directory`. When the command starts it asks for searching a TV Show, enter the title (or part of it) and select from the list. After that the command starts retrieving meta-data for video files found in `directory` that didn't have related meta-data `.xml` file. Use `language` option to get meta-data in the given language.