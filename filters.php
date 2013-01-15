<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$feedID = mysql_real_escape_string($_GET["id"]);

$q = mysql_query("SELECT * FROM filters	WHERE feedID=$feedID ORDER BY whiteorblack ASC, regex ASC");
echo "<filters>";
while ($r = mysql_fetch_object($q)){
	echo "<filter><id>$r->ID</id><regex>$r->regex</regex><whiteorblack>$r->whiteorblack</whiteorblack></filter>";
}
echo "</filters>";
?>
