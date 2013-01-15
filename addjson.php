<?
include("config.php");
header ("Content-Type: text/plain");

$json = array();
$json["status"] = "Error";
if ($ISDEMO){
	$json["error"] = "This is just a demo page.";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);

	if ($_POST["name"] == ""){
		$f = fopen($_POST["url"], "rb");
		$stream = stream_get_contents($f);
		fclose($f);
		$stream = preg_replace("/\n/", "", $stream);
		preg_match("/<title>([^<]*)<\/title>/", $stream, $matches);
		$_POST["name"] = $matches[1];
		if ($_POST["name"] == "")
			$_POST["name"] = "Unknown feed";
	}
	foreach (Array("name", "url", "parentID") as $x)
		$_POST[$x] = mysql_real_escape_string($_POST[$x]);
	$q = mysql_query("SELECT * FROM feeds WHERE `url` = '".$_POST['url']."' AND `url` != ''");
	if (mysql_num_rows($q) > 0){
		$r = mysql_fetch_object($q);
		$json["error"] = "Duplicate URL. URL in use in feed $r->name!";
	} else {
		$q = mysql_query("SELECT * FROM feeds WHERE ID = '".$_POST['parentID']."'");
		$r = mysql_fetch_object ($q);
		mysql_query("UPDATE feeds SET endID=endID+2 WHERE endID >= $r->endID");
		mysql_query("UPDATE feeds SET startID=startID+2 WHERE startID >= $r->endID"); # $r->endID is correct!
		$q = mysql_query("INSERT INTO feeds (startID, endID, name, url) VALUES (".
				 "".$r->endID.", ".
				 "".($r->endID+1).", ".
				 "'".$_POST['name']."', ".
				 "'".$_POST['url']."')");
		if (mysql_error()){
			$json["error"] = mysql_error();
		} else {
			$json["status"] = "OK";
		}
	}
}
echo json_encode($json);
?>
