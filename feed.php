<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";
echo "<feed><id>".$_GET['id']."</id>";
mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
if ($_GET['onlychecked'] == 1){
	$q = mysql_query("SELECT * FROM entries WHERE feedID = ".mysql_real_escape_string($_GET['id'])." AND isread='0' ORDER BY date DESC");
} else {
	$q = mysql_query("SELECT * FROM entries WHERE feedID = ".mysql_real_escape_string($_GET['id'])." ORDER BY date DESC");
}
while ($r = mysql_fetch_object($q)){
	if ($r->title == ""){
		$r->title="no title";
	}
	echo "<item><id>$r->ID</id><title>".htmlspecialchars($r->title)."</title><content>";
	foreach (split("\n", str_replace("<p>", "\n", str_replace("<br>", "\n", $r->description))) as $key => $value){
		$value = htmlspecialchars(($value));
		if ($value == ""){
			$value = "&amp;nbsp;";
		}
		echo "<line>".$value."</line>";
	}
	echo "</content><link>".htmlspecialchars($r->link)."</link><isread>$r->isread</isread></item>";
}
echo "</feed>";
?>
