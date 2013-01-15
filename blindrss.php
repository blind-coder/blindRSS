<?
include("config.php");
include("xmlParser-0.3.php");
include("feedParser-0.5.php");

function blindparse($url){
	$fp = new feedParser();
	ini_set('default_socket_timeout', 120);
	$xml = file_get_contents($url);
	$xml = preg_replace("/ß/", "&szlig;", $xml);
	$xml = preg_replace("/Ö/", "&Ouml;", $xml);
	$xml = preg_replace("/Ä/", "&Auml;", $xml);
	$xml = preg_replace("/Ü/", "&Uuml;", $xml);
	$xml = preg_replace("/ö/", "&ouml;", $xml);
	$xml = preg_replace("/ä/", "&auml;", $xml);
	$xml = preg_replace("/ü/", "&uuml;", $xml);
	return $fp->parseFeed($xml);
}

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) || die (mysql_error());
mysql_select_db($MYSQL_DB) || die (mysql_error());
$q = mysql_Query("SELECT * FROM feeds WHERE `url` != '' AND `url` IS NOT NULL");

while ($r = mysql_fetch_object($q)){
	print_r($r);
	$feed = blindparse($r->url);
	print_r($feed);
	foreach ($feed[item] as $key => $value){
		if ($value[date] == ""){
			$value[date]=time();
		}
		mysql_query("INSERT INTO entries (feedID, title, link, description, date, isread) VALUES (".
				"(SELECT ID FROM feeds WHERE url = '$r->url'), ".
				"'".mysql_real_escape_string($value[title])."',".
				"'".mysql_real_escape_string($value[link])."',".
				"'".mysql_real_escape_string($value[description])."',".
				"FROM_UNIXTIME( '".mysql_real_escape_string($value[date])."'),".
				"'0')");
		echo mysql_error();
	}
}
mysql_close();
