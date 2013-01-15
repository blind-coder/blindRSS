<?
include("config.php");
define("MAGPIE_CACHE_ON", FALSE);
define('MAGPIE_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1');
require_once("rss_fetch.inc");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) || die (mysql_error());
mysql_select_db($MYSQL_DB) || die (mysql_error());
mysql_query("SELECT `value` INTO @x FROM options WHERE `key` = 'purgeAfter'; DELETE FROM `entries` WHERE `date` < SUBDATE(CURDATE(), INTERVAL @x DAY);");
$q = mysql_query("SELECT * FROM feeds WHERE `url` != '' AND `url` IS NOT NULL AND `url` != 'SEARCHRESULTS'");

function sanitize($v){
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
while ($r = mysql_fetch_object($q)){
	echo "Fetching $r->url\n";
	$feed = fetch_rss($r->url);
	foreach ($feed->items as $value){
		if ($value['date_timestamp'] == ""){
			$value['date_timestamp']=time();
		}
		$title = mysql_real_escape_string(sanitize($value['title']));
		$description = mysql_real_escape_string(sanitize($value['description']));
		$link = mysql_real_escape_string($value['link']);
		if (preg_match('/^http/', $value['guid']) == 1){
			echo "Using guid '".$value['guid']."' instead of  link '$link'\n";
			$link = mysql_real_escape_string($value['guid']);
		}
		$timestamp = mysql_real_escape_string($value['date_timestamp']);
		$isread = '0';
		$filterq = mysql_query("SELECT * FROM filter WHERE feedID = ".$r->ID." AND CONCAT('$title', '$description') LIKE regex ORDER BY ID");
		while ($filterr = mysql_fetch_object($filterq)){
			if ($filterr->whiteorblack == "black"){
				$isread = '1';
			} else {
				$isread = '0';
			}
		}
		mysql_query("INSERT INTO entries (feedID, title, link, description, date, isread) VALUES (".
				"(SELECT ID FROM feeds WHERE url = '$r->url'), ".
				"'$title','$link','$description', ".
				"FROM_UNIXTIME( '$timestamp'),'$isread') ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),date=VALUES(date),isread=VALUES(isread)");
		if (mysql_error()){
			echo mysql_error()."\n";
		}
	}
}
mysql_close();
