<?php
include("etc/config.php");
require_once("autoloader.php");

($link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB)) || die (mysqli_error($link));
mysqli_query($link, "SET NAMES 'utf8';");

function my_mysqli_query($link, $query){
	$retval = mysqli_query($link, $query);
	if (mysqli_error($link)){
		echo "Mysql error: ".mysqli_error($error)."\nQuery was: $query\n";
	}
	return $retval;
}

/* Purge old entries */
my_mysqli_query($link, "
DELETE FROM `entries` WHERE `date` < SUBDATE(CURDATE(), INTERVAL (SELECT `value` FROM options WHERE `key` = 'purgeAfter') DAY)
	AND `favorite` IN ('no', (SELECT `value` FROM options WHERE `key` = 'deleteFavorites'))
	AND IF((SELECT `value` FROM options WHERE `key` = 'deleteTagged') = 'no', NOT(SELECT COUNT(ID) FROM entries_tags WHERE entryID = entries.ID LIMIT 1), 1);
");

$query_feeds = my_mysqli_query($link, "SELECT * FROM feeds WHERE `url` != '' AND `url` IS NOT NULL AND `url` != 'SEARCHRESULTS'");
while ($feed = mysqli_fetch_object($query_feeds)){
	echo "Fetching $feed->url\n";
	$SimplePie = new SimplePie();
	$SimplePie->set_feed_url($feed->url);
	$SimplePie->enable_cache(false);
	$SimplePie->set_useragent("blindRSS");
	$SimplePie->init();
	$SimplePie->handle_content_type();
	if (!$SimplePie){ continue; }
	$feedfilter = array();
	$query = sprintf("SELECT * FROM filter WHERE feedID = '%s'", $feed->ID);
	$query_filter = my_mysqli_query($link, $query);
	if ($query_filter){
		while ($filter = mysqli_fetch_object($query_filter)){
			$feedfilter[] = $filter;
		}
	}
	foreach ($SimplePie->get_items() as $feedEntry){
		$title = $feedEntry->get_title();
		while ($title !== html_entity_decode($title, ENT_QUOTES, "UTF-8")){
			$title = html_entity_decode($title, ENT_QUOTES, "UTF-8");
		}
		$content = $feedEntry->get_content();
		$timestamp = $feedEntry->get_date("U");
		$guid = $feedEntry->get_id();
		$entrylink = $feedEntry->get_link();
		if ($timestamp == null){
			$timestamp = time();
		}
		if ("x$content" == "x"){
			$content = "No content.";
		}

		/* Set one last default */
		$isread = 0;

		/* Check filter lists
		 * Here, last matching filter always wins.
		 */
		foreach ($feedfilter AS $key => $value){
			if (preg_match("/{$value->regex}/i", $title) ||
			    preg_match("/{$value->regex}/i", $content)){
				if ($value->whiteorblack == "white"){
					$isread = "0";
				} elseif ($value->whiteorblack == "ignore"){
					$isread = "-1";
				} else {
					$isread = "1";
				}
			}
		}

		if ($isread == "-1"){
			continue;
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
						$desc = preg_replace(",src=[\"\']?".preg_quote($value, ',')."[\"\']?,i", "src=\"cache.php?q=".base64_encode($url)."\"", $desc);
					} else {
						$query = sprintf("SELECT * FROM cache WHERE sha1_link='%s'", $url_sha1);
						$query_cache = my_mysqli_query($link, $query);
						if (mysqli_num_rows($link, $query_cache) == 0){
							$content = file_get_contents($url);
							$content = base64_encode($content);
							$query = sprintf("INSERT INTO cache (link, sha1_link, content) VALUES ('%s', '%s', '%s')",
								$url, $url_sha1, $content);
							my_mysqli_query($link, $query);
						}
						$desc = preg_replace(",src=[\"\']?".preg_quote($value, ',')."[\"\']?,i", "src=\"cache.php?q=".$url_sha1."\"", $desc);
					}
				}
			}
			$content = $desc;
		}

		/* Finally update the database */
		$query = sprintf("SELECT * FROM entries WHERE feedID = '%s' AND sha1_link = '%s'", $feed->ID, sha1($entrylink));
		$query_oldentry = my_mysqli_query($link, $query);
		if (mysqli_num_rows($query_oldentry) > 0){
			$oldFeedEntry = mysqli_fetch_array($query_oldentry);
			/* We have an old entry in this feed with this URL, now check if it's new */
			if ($oldFeedEntry["title"] != $title ||
			    $oldFeedEntry["description"] != $content){
				$query = sprintf("UPDATE entries SET title='%s', description='%s', date=FROM_UNIXTIME(%s) WHERE ID = '%s'",
					mysqli_real_escape_string($link, $title),
					mysqli_real_escape_string($link, $content),
					$timestamp,
					$oldFeedEntry["ID"]);
				$query_oldFeedEntry = my_mysqli_query($link, $query);
				/* Reset isread? */
				if ($isread == 1){ // forced read by blacklist
					$query = sprintf("UPDATE entries SET isread='1' WHERE ID = '%s'",
						$oldFeedEntry["ID"]);
					my_mysqli_query($link, $query);
				}
				elseif ($feed->unreadOnChange == "yes"){
					if ($oldFeedEntry["isread"] == "1" && $isread == "0"){
						$query = sprintf("UPDATE entries SET isread='0' WHERE ID = '%s'",
							$oldFeedEntry["ID"]);
						my_mysqli_query($link, $query);
					}
				}
			}
		} else {
			$query = sprintf("INSERT INTO entries (feedID, title, link, sha1_link, description, date, isread) ".
				"VALUES ('%s', '%s', '%s', '%s', '%s', FROM_UNIXTIME(%s), '%s')",
				$feed->ID,
				mysqli_real_escape_string($link, $title),
				mysqli_real_escape_string($link, $entrylink),
				sha1(mysqli_real_escape_string($link, $entrylink)),
				mysqli_real_escape_string($link, $content),
				mysqli_real_escape_string($link, $timestamp),
				$isread);
			my_mysqli_query($link, $query);
		}
	}
}
mysqli_close($link);
