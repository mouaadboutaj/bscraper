<?php

/**
 * BScraper - A library to crawl websites for Tor
 *
 * @author Mouaad Boutaj
 * @license BScraper
 * @link https://github.com/mouaadboutaj

 *  The BScraper library helps you to crawl Tor's websites
 *  and extract information from them
 */

namespace Boutaj\Scraper;

class BScraper
{
   /**
	 * The tor socks proxy
	 * @var string
	 */
   public $proxy;

   /**
	 * The user agent
	 * @var string
     */
   public $ua;

    /**
     * BScraper constructor.
     */
   public function __construct()
	{
	    ini_set('display_errors', 0);
		if (version_compare(PHP_VERSION, '5.3', '>='))
		{
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		}
		else
		{
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
		}

		$this->proxy = '127.0.0.1:9050';
		$this->ua = 'Mozilla/5.0 (Windows NT 10.0; rv:68.0) Gecko/20100101 Firefox/68.0';
	}

    /**
     * Curl library settings for tor scraping
     * @param $url
     * @param $ch
     * @param $setup_curl
     * @return boolean
     */
   public function curl_settings($url, $ch, $setup_curl = null)
   {
        if (!is_string($url) or !strlen($url = trim($url)))
        {
            die('$url must be a non-empty trimmed string.');
            return false;
        }

        if (!preg_match('~(^(https?://)?([a-z0-9]*\.)?([a-z2-7]{16}|[a-z2-7]{56})(\.onion(/.*)?)?$)~i', $url, $matches))
        {
            die('$url must be a onion valid URL.');
            return false;
        }

        if (!extension_loaded('curl'))
        {
            die('cURL extension must be loaded.');
            return false;
        }

   	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->ua);
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($setup_curl and is_callable($setup_curl))
        {
            call_user_func($setup_curl, $ch);
        }

        return true;
   }

    /**
     * Get Tor website contents
     * @param $url
     * @return string
     */
   public function get_contents($url)
   {
   	    $ch = curl_init();
        $this->curl_settings($url, $ch);
        $data = curl_exec($ch);
        curl_close($ch);
        $content = ($data) ? $data : false;
        return $content;
   }

    /**
     * Get Tor website headers
     * @param $url
     * @return array
     */
   public function get_headers($url)
   {    
   	    $ch = curl_init();
   	    $this->curl_settings($url, $ch);
        $response_buckets = array();
        if (is_string($response = curl_exec($ch)))
        {
            $response_buckets = $response_bucket = array();
            foreach ($response as $response_line)
            {
                if (empty($response_line))
                {
                    if (empty($response_bucket))
                    {
                        continue;
                    }
                    $response_buckets[] = $response_bucket;
                    $response_bucket = array();
                    continue;
                }
                $response_bucket[] = $response_line;
            }
            if (!empty($response_bucket))
            {
                $response_buckets[] = $response_bucket;
                $response_bucket = array();
            }
            foreach ($response_buckets as $response_key => $response_bucket)
            {
                $response_bucket_formatted = array();
                foreach ($response_bucket as $response_line)
                {
                    if (preg_match('~^HTTP/([0-9]+\\.[0-9]+)\\s+([0-9]+)\\s+(.+)~', $response_line, $slices))
                    {
                        $response_bucket_formatted['X:ProtocolVersion'] = $slices[1];
                        $response_bucket_formatted['X:StatusCode'] = intval($slices[2]);
                        $response_bucket_formatted['X:StatusMessage'] = trim($slices[3]);
                        continue;
                    }
                    if (preg_match('~^(.+?):(.+)$~', $response_line, $slices))
                    {
                        $header_name = trim($slices[1]);
                        $header_value = trim($slices[2]);
                        if (!empty($response_bucket_formatted[$header_name]))
                        {
                            if (!is_array($response_bucket_formatted[$header_name]))
                            {
                                $response_bucket_formatted[$header_name] = array(
                                    $response_bucket_formatted[$header_name]
                                );
                            }
                            $response_bucket_formatted[$header_name][] = $header_value;
                            continue;
                        }
                        $response_bucket_formatted[$header_name] = $header_value;
                    }
                    $response_buckets[$response_key] = $response_bucket_formatted;
                }
            }
            $response_bucket = & $response_buckets[count($response_buckets) - 1];
            $response_bucket['X:RequestedUrl'] = curl_getinfo($ch, $url);
            $response_bucket['X:EffectiveUrl'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $response_bucket['X:HttpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response_bucket['X:cURL'] = curl_getinfo($ch);
        }
        curl_close($ch);
        return end($response_buckets);   	    
   }
}