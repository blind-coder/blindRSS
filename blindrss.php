<?
include("etc/config.php");
require_once("autoloader.php");

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
	$SimplePie = new SimplePie();
	$SimplePie->set_feed_url($feed->url);
	$SimplePie->set_useragent("blindRSS");
	$SimplePie->init();
	$SimplePie->handle_content_type();
	if (!$SimplePie){ continue; }
	$feedfilter = array();
	$query = sprintf("SELECT * FROM filter WHERE feedID = '%s'", $feed->ID);
	$query_filter = my_mysql_query($query);
	if ($query_filter){
		while ($filter = mysql_fetch_object($query_filter)){
			$feedfilter[] = $filter;
		}
	}
	foreach ($SimplePie->get_items() as $feedEntry){
		$title = $feedEntry->get_title();
		$content = $feedEntry->get_content();
		$timestamp = $feedEntry->get_date("U");
		$guid = $feedEntry->get_id();
		$link = $feedEntry->get_link();
		if ($timestamp == null){
			$timestamp = time();
		}
		if ("x$content" == "x"){
			$content = "No content.";
		}

		/* Set one last default */
		$isread = 0;

		/* Check filter lists */
		foreach ($feedfilter AS $key => $value){
			if (preg_match("/{$value->regex}/i", $title) ||
			    preg_match("/{$value->regex}/i", $content)){
				if ($value->whiteorblack == "white"){
					$isread = "0";
				} else {
					$isread = "1";
				}
			}
		}

		if ($feed->cacheimages == "yes"){
			$url_base = preg_replace(",/[^/]*$,", "/", $feed->url);
			$url_domain = preg_replace(",^(https?://[^/]*/).*$,", "$1", $feed->url);
			$desc = $content;
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
			$content = $desc;
		}

		/* Finally update the database */
		$query = sprintf("SELECT * FROM entries WHERE feedID = '%s' AND sha1_link = '%s'", $feed->ID, sha1($link));
		$query_oldentry = my_mysql_query($query);
		if (mysql_num_rows($query_oldentry) > 0){
			$oldFeedEntry = mysql_fetch_array($query_oldentry);
			/* We have an old entry in this feed with this URL, now check if it's new */
			if ($oldFeedEntry["title"] != $title ||
			    $oldFeedEntry["description"] != $content){
				$query = sprintf("UPDATE entries SET title='%s', description='%s', date=FROM_UNIXTIME(%s) WHERE ID = '%s'",
					mysql_real_escape_string($title),
					mysql_real_escape_string($content),
					$timestamp,
					$oldFeedEntry["ID"]);
				$query_oldFeedEntry = my_mysql_query($query);
				/* Reset isread? */
				if ($isread == 1){ // forced read by blacklist
					$query = sprintf("UPDATE entries SET isread='1' WHERE ID = '%s'",
						$oldFeedEntry["ID"]);
					my_mysql_query($query);
				}
				elseif ($unreadOnChange){
					if ($oldFeedEntry["isread"] == "1" && $isread == "0"){
						$query = sprintf("UPDATE entries SET isread='0' WHERE ID = '%s'",
							$oldFeedEntry["ID"]);
						my_mysql_query($query);
					}
				}
			}
		} else {
			$query = sprintf("INSERT INTO entries (feedID, title, link, sha1_link, description, date, isread) ".
				"VALUES ('%s', '%s', '%s', '%s', '%s', FROM_UNIXTIME(%s), '%s')",
				$feed->ID,
				mysql_real_escape_string($title),
				mysql_real_escape_string($link),
				sha1(mysql_real_escape_string($link)),
				mysql_real_escape_string($content),
				mysql_real_escape_string($timestamp),
				$isread);
			my_mysql_query($query);
		}
	}
}
mysql_close();
