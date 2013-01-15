<?
include("config.php");
define("MAGPIE_CACHE_ON", FALSE);
require_once("rss_fetch.inc");

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) || die (mysql_error());
mysql_select_db($MYSQL_DB) || die (mysql_error());
mysql_query("SELECT `value` INTO @x FROM options WHERE `key` = 'purgeAfter'; DELETE FROM `entries` WHERE `date` < SUBDATE(CURDATE(), INTERVAL @x DAY);");
$q = mysql_query("SELECT * FROM feeds WHERE `url` != '' AND `url` IS NOT NULL");

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
		if ($value[date_timestamp] == ""){
			$value[date_timestamp]=time();
		}
		$title = $value[title];
		$description = $value[description];
		mysql_query("INSERT INTO entries (feedID, title, link, description, date, isread) VALUES (".
				"(SELECT ID FROM feeds WHERE url = '$r->url'), ".
				"'".mysql_real_escape_string(sanitize($title))."',".
				"'".mysql_real_escape_string($value[link])."',".
				"'".mysql_real_escape_string(sanitize($description))."',".
				"FROM_UNIXTIME( '".mysql_real_escape_string($value[date_timestamp])."'),".
				"'0')");
		echo mysql_error()."\n";
	}
}
mysql_close();
