<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";

if ($ISDEMO){
	echo "<status>This is just a demo page.</status>";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	$q = mysql_query("INSERT INTO feeds (name, url) VALUES (".
			 "'".mysql_real_escape_string($_GET['name'])."', ".
			 "'".mysql_real_escape_string($_GET['url'])."')");
	echo "<status>";
	if (mysql_error()){
		echo mysql_error();
	} else {
		echo "Feed added";
	}
	echo "</status>";
}
?>
