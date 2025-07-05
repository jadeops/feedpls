# feedpls 🥺
RSS feeds for sites that do not support it. 

## Supported
Below are automatically updated via Github Actions (every few hours).

* https://www.incometax.gov.in/iec/foportal/latest-news Latest news from Income Tax Department, India
* https://www.gst.gov.in/ Latest news from GST Department, India
* IdeaPad Pro 5 14IMH9 BIOS updates

## Usage

```
composer install
php generateFeeds.php

# Check feeds/* for rss feeds
# Check logs/* for failures
```

## Fix code format
```
phpcbf *.php tests/*.php
```
