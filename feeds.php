<?
include("config.php");
header ("Content-Type: text/xml");
echo utf8_encode("<?xml version=\"1.0\"?>\n\n");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);

$q = mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
echo utf8_encode("<feeds>");
while ($r = mysql_fetch_object($q)){
	echo utf8_encode("<feed><id>$r->ID</id><name>$r->name</name><startID>$r->startID</startID><endID>$r->endID</endID><url>".($r->url ? $r->url : "NULL")."</url><filters>");
	$fq = mysql_query("SELECT * FROM filter WHERE feedID = $r->ID ORDER BY ID ASC");
	while ($rf = mysql_fetch_object($fq)){
		echo utf8_encode("<filter><ID>$rf->ID</ID><regex>$rf->regex</regex><whiteorblack>$rf->whiteorblack</whiteorblack></filter>");
	}
	echo utf8_encode("</filters></feed>");
}
echo utf8_encode("</feeds>");
?>
