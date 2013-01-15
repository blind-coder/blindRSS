<?
include("config.php");
header ("Content-Type: text/xml");
echo "<?xml version=\"1.0\"?>\n\n";

if ($ISDEMO){
	echo "<status>This is just a demo page.</status>";
} else {
	mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
	mysql_select_db($MYSQL_DB);
	$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['feedid'])."'");
	if (!(mysql_num_rows($q) > 0)){
		echo "<status>Unknown feed ID!</status>";
		exit;
	}
	$feedToChange = mysql_fetch_object($q);
	if ($feedToChange->startID == 1){
		echo "<status>Refusing to change master directory!</status>";
		exit;
	}
	mysql_query("UPDATE `feeds` SET url='".mysql_real_escape_string($_GET['newurl'])."', name='".
			mysql_real_escape_string($_GET['newtitle'])."' WHERE ID='".
			mysql_real_escape_string($_GET['feedid'])."'");
	if ($_GET['feedid'] == $_GET['moveabove']){
		echo "<status>Updated name/title only</status>";
		exit;
	}

	$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['moveabove'])."'");
	if (!(mysql_num_rows($q) > 0)){
		echo "<status>Unknown moveabove ID!</status>";
		exit;
	}
	$feedToMoveTo = mysql_fetch_object($q);
	if ($feedToMoveTo->url == ""){{{
		mysql_query("UPDATE feeds SET movedirection = 'moveme' WHERE startID >= $feedToChange->startID AND endID <= $feedToChange->endID");
		$difference = ($feedToChange->endID - $feedToChange->startID) + 1;

		mysql_query("UPDATE feeds SET startID = startID - $difference WHERE startID > $feedToChange->endID");
		mysql_query("UPDATE feeds SET endID = endID - $difference WHERE endID > $feedToChange->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['moveabove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['feedid'])."'");
		$feedToChange = mysql_fetch_object($q);

		mysql_query("UPDATE feeds SET startID = startID + $difference WHERE startID >= $feedToMoveTo->endID");
		mysql_query("UPDATE feeds SET endID = endID + $difference WHERE endID >= $feedToMoveTo->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['moveabove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['feedid'])."'");
		$feedToChange = mysql_fetch_object($q);

		/* Move to Top
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->startID + 1 WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->startID + 1 WHERE movedirection = 'moveme'");
		 */
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->endID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->endID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET movedirection = 'none'");

			/*
			   Start				End
		1  1----------+                             1  1----------+
		   |          |                                |          |
		2  |          2------------+                2  |          2------------+
		   |          |            |                   |          |            |
		3  |          |            3-------------+  3  |          |            3-------------+
		   |          | (2)Windows | (6)Appl     |     |          | (3)Linux   | (5)Strategy |
		4  |          |            4-------------+  4  |          |            4-------------+
		   |          |            |                   |          |            |
		5  |          5------------+                5  |          5------------+
		   |          |                                |          |
		6  |          6------------+                6  |          6------------+
		   |          | (3)Linux   |                   |          |            |
		7  |          7------------+                7  |          |            7------------+
		   | (1)Games |                                | (1)Games |            |            |
		8  |          8------------+                8  |          |            |            8-------------+
		   |          | (5)MIPS    |                   |          | (4)Mac     | (2)Windows | (6)Appl     |
		9  |          9------------+                9  |          |            |            9-------------+
		   |          |                                |          |            |            |
		10 |          10-----------+                10 |          |            10-----------+
		   |          | (4)Mac     |                   |          |            |
		11 |          11-----------+                11 |          11-----------+
		   |          |                                |          |
		12 12---------+                             12 12---------+ */
		echo "<status>Feed moved!</status>";
	}}}
	else {{{
		mysql_query("UPDATE feeds SET movedirection = 'moveme' WHERE startID >= $feedToChange->startID AND endID <= $feedToChange->endID");
		$difference = ($feedToChange->endID - $feedToChange->startID) + 1; # 6

		mysql_query("UPDATE feeds SET startID = startID - $difference WHERE startID > $feedToChange->endID");
		mysql_query("UPDATE feeds SET endID = endID - $difference WHERE endID > $feedToChange->endID");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['moveabove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['feedid'])."'");
		$feedToChange = mysql_fetch_object($q);
#4-5
		mysql_query("UPDATE feeds SET startID = startID + $difference WHERE startID >= $feedToMoveTo->startID AND movedirection = 'none'");
		mysql_query("UPDATE feeds SET endID = endID + $difference WHERE endID > $feedToMoveTo->startID AND movedirection = 'none'");

		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['moveabove'])."'");
		$feedToMoveTo = mysql_fetch_object($q);
		$q = mysql_query("SELECT * FROM feeds WHERE `ID` = '".mysql_real_escape_string($_GET['feedid'])."'");
		$feedToChange = mysql_fetch_object($q);

#10-11
		mysql_query("UPDATE feeds SET startID = startID - $feedToChange->startID + $feedToMoveTo->startID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET endID = endID - $feedToChange->startID + $feedToMoveTo->startID - $difference WHERE movedirection = 'moveme'");
		mysql_query("UPDATE feeds SET movedirection = 'none'");
		echo "<status>Feed moved!</status>";
/*
$feedToChange = (                                $feedToMoveTo = (
    [ID] => 75                                       [ID] => 1
    [startID] => 154                                 [startID] => 5
    [endID] => 155                                   [endID] => 6
    [name] => NNFB                                   [name] => TechSucks
    [url] => http://feeds.feedburner.com/nnfb        [url] => http://blog.crash-override.net/rss.php
    [movedirection] => none                          [movedirection] => none
)                                                ) */

	}}}
}
?>
