# BScreaper
BScreaper. A library to crawl websites for Tor

The BScraper library helps you to crawl Tor's websites and extract information from them


## Using

**General syntax**

```php
use Boutaj\Scraper;

$BScraper = Nnw BScraper();
```
**Get Tor website contents**

```php
use Boutaj\Scraper;

$BScraper = new BScraper();

$url = 'http://msydqstlz2kzerdg.onion';

echo $BScraper->get_contents($url);
```
**Get Tor website headers**

```php
use Boutaj\Scraper;

$BScraper = new BScraper();

$url = 'http://msydqstlz2kzerdg.onion';

echo $BScraper->get_headers($url);
```

### Requirements
| Software      | Modules      |
| ------------- | -------------|
| PHP >=7       | curl         |

### Installation
1. Install Git [https://github.com/git-guides/install-git]
2. Write on the terminal `git clone https://github.com/mouaadboutaj/bscraper.git`
2. Setup `BScraper.php` in your server

Happy coding.
