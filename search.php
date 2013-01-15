<?
include("config.php");
header ("Content-Type: text/xml");
echo utf8_encode("<?xml version=\"1.0\"?>");
echo utf8_encode("<feed>");
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
$q = mysql_query("SELECT * FROM feeds WHERE url = 'SEARCHRESULTS'");
$r = mysql_fetch_object($q);
echo utf8_encode("<id>".$r->ID."</id>");
$q = mysql_query("SELECT * FROM entries WHERE description LIKE '%".mysql_real_escape_string($_GET['q'])."%' ORDER BY date DESC");
echo mysql_error();
while ($r = mysql_fetch_object($q)){
	if ($r->title == ""){
		$r->title="no title";
	}
	echo utf8_encode("<item><id>$r->ID</id><title>".htmlspecialchars($r->title)."</title><content>");
	foreach (split("\n", str_replace("<p>", "\n", str_replace("<br>", "\n", $r->description))) as $key => $value){
		$value = str_replace($_GET['q'], "<a class='searchMatch'>".$_GET['q']."</a>", $value);
		$value = htmlspecialchars($value);
		if ($value == ""){
			$value = "&amp;nbsp;";
		}
		echo utf8_encode("<line>".$value."</line>");
	}
	echo utf8_encode("</content><link>".htmlspecialchars($r->link)."</link><isread>$r->isread</isread></item>");
}
echo utf8_encode("</feed>");
?>
