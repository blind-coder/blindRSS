<?
include("config.php");
header ("Content-Type: application/octet-stream");
#header ("Content-Type: text/plain");

if (is_writable($CACHEDIR)){
	$url = base64_decode($_GET['q']);
	preg_match(",^https?://(.*?)/(.*)$,", $url, $url_match);
	$url_match[1] = preg_replace(",[^A-Za-z0-9\.],", "_", $url_match[1]);
	$url_match[2] = preg_replace(",[^A-Za-z0-9\.],", "_", $url_match[2]);
	$dir = $CACHEDIR."/".$url_match[1];
	readfile($dir."/".$url_match[2]);
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$r = mysql_query("SELECT * FROM cache WHERE sha1_link = '".$_GET['q']."'");
	$q = mysql_fetch_object($r);
	echo base64_decode($q->content);
}
?>
