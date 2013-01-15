<?
include("etc/config.php");
define("MAGPIE_CACHE_ON", FALSE);
define('MAGPIE_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1');
require_once("etc/rss_fetch.inc");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) || die (mysql_error());
mysql_select_db($MYSQL_DB) || die (mysql_error());
mysql_query("SET NAMES 'utf8';");

/* Purge old entries */
mysql_query("SELECT `value` INTO @x FROM options WHERE `key` = 'purgeAfter'; DELETE FROM `entries` WHERE `date` < SUBDATE(CURDATE(), INTERVAL @x DAY);");

$q = mysql_query("SELECT `value` FROM options WHERE `key` = 'unreadOnChange'");
$unreadOnChange = mysql_fetch_object($q);
if ($unreadOnChange->value == "true"){
	$unreadOnChange = true;
} else {
	$unreadOnChange = false;
}

function sanitize($v){ // substitute some Umlauts
	$w = html_entity_decode(htmlentities($v));
	$w = str_replace(html_entity_decode("&auml;"), "&auml;", $w);
	$w = str_replace(html_entity_decode("&Auml;"), "&Auml;", $w);
	$w = str_replace(html_entity_decode("&ouml;"), "&ouml;", $w);
	$w = str_replace(html_entity_decode("&Ouml;"), "&Ouml;", $w);
	$w = str_replace(html_entity_decode("&uuml;"), "&uuml;", $w);
	$w = str_replace(html_entity_decode("&Uuml;"), "&Uuml;", $w);
	$w = str_replace(html_entity_decode("&szlig;"), "&szlig;", $w);
	return $w;
}

function my_mysql_query($query){
	$retval = mysql_query($query);
	if (mysql_error()){
		echo "Mysql error: ".mysql_error()."\nQuery was: $query\n";
	}
	return $retval;
}

$query_feeds = my_mysql_query("SELECT * FROM feeds WHERE `url` != '' AND `url` IS NOT NULL AND `url` != 'SEARCHRESULTS'");
while ($feed = mysql_fetch_object($query_feeds)){
	echo "Fetching $feed->url\n";
	$magpie = fetch_rss($feed->url);
	if (!$magpie){ continue; }
	$feedfilter = array();
	$query = sprintf("SELECT * FROM filter WHERE feedID = '%s'", $feed->ID);
	$query_filter = my_mysql_query($query);
	if ($query_filter){
		while ($filter = mysql_fetch_object($query_filter)){
			$feedfilter[] = $filter;
		}
	}
	foreach ($magpie->items as $feedEntry){
		/* Sanitize some Umlauts */
		$feedEntry['title'] = sanitize($feedEntry['title']);
		if (!array_key_exists("description", $feedEntry)){
			$feedEntry["description"] = "no content";
		}
		$feedEntry['description'] = sanitize($feedEntry['description']);

		/* We only deal in UTF8 */
		foreach ($feedEntry AS $key => $value){
			if (is_string($value)){
				$feedEntry[$key] = utf8_encode($value);
			}
		}

		/* Make sure we have a valid timestamp */
		if (array_key_exists("date_timestamp", $feedEntry)){
			if ($feedEntry['date_timestamp'] == ""){
				$feedEntry['date_timestamp']=time();
			}
		} else {
			$feedEntry['date_timestamp']=time();
		}

		/* Make sure we have a valid description */
		if (!array_key_exists("description", $feedEntry)){
			$feedEntry['description'] = "No description.";
		}

		/* Check for GUID */
		if (array_key_exists("guid", $feedEntry)){
			if (preg_match('/^http/', $feedEntry['guid']) && $feedEntry['guid'] != $feedEntry['link']){
				$feedEntry['link'] = $feedEntry['guid'];
			}
		} elseif (!array_key_exists("link", $feedEntry)){
			/* huh?! */
			$feedEntry["link"] = $feedEntry["title"];
		}

		/* Set one last default */
		$feedEntry['isread'] = '0';

		/* Check filter lists */
		foreach ($feedfilter AS $key => $value){
			if (preg_match("/".$value->regex."/i", $feedEntry["title"]) ||
			    preg_match("/".$value->regex."/i", $feedEntry["description"])){
				if ($value->whiteorblack == "white"){
					$feedEntry['isread'] = "0";
				} else {
					$feedEntry['isread'] = "1";
				}
			}
		}

		if ($feed->cacheimages == "yes"){
			$url_base = preg_replace(",/[^/]*$,", "/", $feed->url);
			$url_domain = preg_replace(",^(https?://[^/]*/).*$,", "$1", $feed->url);
			$desc = $feedEntry["description"];
			if (preg_match_all(",<img.*?src=[\"\']?(.*?)[\"\']?[ >],i", $desc, $match) > 0){
				foreach ($match[1] AS $key => $value){
					$url = $value;
					if (substr($url, 0, 1) == "/"){
						$url = $url_domain.substr($url, 1);
					} elseif (strpos($url, "://") == FALSE){
						$url = $url_base.$url;
					}
					$url_sha1 = sha1($url);
					if (is_writable($CACHEDIR)){
						preg_match(",^https?://(.*?)/(.*)$,", $url, $url_match);
						$url_match[1] = preg_replace(",[^A-Za-z0-9\.],", "_", $url_match[1]);
						$url_match[2] = preg_replace(",[^A-Za-z0-9\.],", "_", $url_match[2]);
						$dir = $CACHEDIR."/".$url_match[1];
						if (!file_exists($dir)){
							mkdir($dir, 0700, true);
						}
						if (!file_exists($dir."/".$url_match[2])){
							$content = file_get_contents($url);
							$fd = fopen($dir."/".$url_match[2], "w");
							fwrite($fd, $content);
							fclose($fd);
						}
						$desc = preg_replace(",src=[\"\']?".preg_quote($value, ',')."[\"\']?,i", "src=\"rest.php/cache/".base64_encode($url)."\"", $desc);
					} else {
						$query = sprintf("SELECT * FROM cache WHERE sha1_link='%s'", $url_sha1);
						$query_cache = my_mysql_query($query);
						if (mysql_num_rows($query_cache) == 0){
							$content = file_get_contents($url);
							$content = base64_encode($content);
							$query = sprintf("INSERT INTO cache (link, sha1_link, content) VALUES ('%s', '%s', '%s')",
								$url, $url_sha1, $content);
							my_mysql_query($query);
						}
						$desc = preg_replace(",src=[\"\']?".preg_quote($value, ',')."[\"\']?,i", "src=\"rest.php/cache/".$url_sha1."\"", $desc);
					}
				}
			}
			$feedEntry["description"] = $desc;
		}

		/* Finally update the database */
		$query = sprintf("SELECT * FROM entries WHERE feedID = '%s' AND sha1_link = '%s'", $feed->ID, sha1($feedEntry['link']));
		$query_oldentry = my_mysql_query($query);
		if (mysql_num_rows($query_oldentry) > 0){
			$oldFeedEntry = mysql_fetch_array($query_oldentry);
			/* We have an old entry in this feed with this URL, now check if it's new */
			if ($oldFeedEntry["title"] != $feedEntry["title"] ||
			    $oldFeedEntry["description"] != $feedEntry["description"]){
				$query = sprintf("UPDATE entries SET title='%s', description='%s' WHERE ID = '%s'",
					mysql_real_escape_string($feedEntry["title"]),
					mysql_real_escape_string($feedEntry["description"]),
					$oldFeedEntry["ID"]);
				$query_oldFeedEntry = my_mysql_query($query);
				/* Reset isread? */
				if ($feedEntry["isread"] == 1){ // forced read by blacklist
					$query = sprintf("UPDATE entries SET isread='1' WHERE ID = '%s'",
						$oldFeedEntry["ID"]);
					my_mysql_query($query);
				}
				elseif ($unreadOnChange){
					if ($oldFeedEntry["isread"] == "1" && $feedEntry["isread"] == "0"){
						$query = sprintf("UPDATE entries SET isread='0' WHERE ID = '%s'",
							$oldFeedEntry["ID"]);
						my_mysql_query($query);
					}
				}
			}
		} else {
			$query = sprintf("INSERT INTO entries (feedID, title, link, sha1_link, description, date, isread) ".
				"VALUES ('%s', '%s', '%s', '%s', '%s', FROM_UNIXTIME('%s'), '%s')",
				$feed->ID,
				mysql_real_escape_string($feedEntry["title"]),
				mysql_real_escape_string($feedEntry["link"]),
				sha1(mysql_real_escape_string($feedEntry["link"])),
				mysql_real_escape_string($feedEntry["description"]),
				mysql_real_escape_string($feedEntry["date_timestamp"]),
				$feedEntry["isread"]);
			my_mysql_query($query);
		}
	}
}
mysql_close();
