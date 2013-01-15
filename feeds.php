<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$q = mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
echo "<feeds>";
while ($r = mysql_fetch_object($q)){
	echo "<feed><id>$r->ID</id><name>$r->name</name><startID>$r->startID</startID><endID>$r->endID</endID><url>".($r->url ? $r->url : "NULL")."</url></feed>";
}
echo "</feeds>";
?>
