<?php
include("etc/config.php");
require_once("autoloader.php");
function mres($x){
	return mysql_real_escape_string($x);
}
function my_mysql_query($x){/*{{{*/
	//error_log($x);
	return mysql_query($x);
}/*}}}*/
function xml_encode($mixed, $domElement=null, $DOMDocument=null) {/*{{{*/
	if (is_null($DOMDocument)) {
		$DOMDocument =new DOMDocument;
		$DOMDocument->formatOutput = true;
		xml_encode($mixed, $DOMDocument, $DOMDocument);
		return $DOMDocument->saveXML();
	}
	else {
		if (is_array($mixed)) {
			foreach ($mixed as $index => $mixedElement) {
				if (is_int($index)) {
					if ($index === 0) {
						$node = $domElement;
					}
					else {
						$node = $DOMDocument->createElement($domElement->tagName);
						$domElement->parentNode->appendChild($node);
					}
				}
				elseif (is_string($mixedElement)){
					$domAttr = $DOMDocument->createAttribute($index);
					$domAttr->value = $mixedElement;
					$domElement->appendChild($domAttr);
					$node = $domElement;
					continue;
				}
				else {
					$plural = $DOMDocument->createElement($index);
					$domElement->appendChild($plural);
					$node = $plural;
					if (!(rtrim($index, 's') === $index)) {
						$singular = $DOMDocument->createElement(rtrim($index, 's'));
						$plural->appendChild($singular);
						$node = $singular;
					}
				}

				xml_encode($mixedElement, $node, $DOMDocument);
			}
		}
		else {
			$domElement->appendChild($DOMDocument->createTextNode($mixed));
		}
	}
}/*}}}*/
function addFeed($feed){/*{{{*/
	$retVal = Array();
	global $ISDEMO;
	if ($ISDEMO){
		$retVal["status"] = "error";
		$retVal["msg"] = "This is a demo :-)";
		return $retVal;
	}

	$SimplePie = new SimplePie();
	$SimplePie->set_feed_url($feed["url"]);
	$SimplePie->enable_cache(false);
	$SimplePie->set_useragent("blindRSS");
	$SimplePie->init();
	$SimplePie->handle_content_type();

	if ($SimplePie->get_link() != null){
		if (($f = fopen("http://g.etfv.co/".$SimplePie->get_link()."?defaulticon=none","rb")) == true){
			$img = stream_get_contents($f);
			fclose($f);
			if (preg_match("/DOCTYPE/", $img) || $img == ""){
				$feed["favicon"] = "";
			} else {
				$feed["favicon"] = "data:image;base64,".base64_encode($img);
			}
		} else {
			$feed["favicon"] = "";
		}
	}

	if ($feed["name"] == "" && "x".$feed["url"] != "x"){
		$feed["name"] = $SimplePie->get_title();
		if ($feed["name"] == "")
			$feed["name"] = "Unknown feed";
	}

	$q = my_mysql_query("SELECT * FROM feeds WHERE `url` = '".$feed["url"]."' AND `url` != ''");
	if (mysql_num_rows($q) > 0){
		$r = mysql_fetch_object($q);
		$retVal["status"] = "Error";
		$retVal["name"] = $feed["name"];
		$retVal["msg"] = "Duplicate URL! URL already in use in Feed {$r->name}!";
		return $retVal;
	}

	$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($feed["parent"]));
	if (mysql_error()){
		$retVal["status"] = "Error";
		$retVal["msg"] = "Unknown Parent Feed";
		return $retVal;
	}

	$r = mysql_fetch_object($q);
	my_mysql_query("START TRANSACTION");
	my_mysql_query("UPDATE feeds SET endID=endID+2 WHERE endID >= $r->endID");
	my_mysql_query("UPDATE feeds SET startID=startID+2 WHERE startID >= $r->endID"); # $r->endID is correct!
	$q = my_mysql_query("INSERT INTO feeds (startID, endID, cacheimages, unreadOnChange, name, url, favicon)
		VALUES ({$r->endID}, {$r->endID}+1, '".mres($feed["cacheimages"])."', '".mres($feed["unreadOnChange"])."', '".mres($feed["name"])."', '".mres($feed["url"])."', '".mres($feed["favicon"])."')");
	if (mysql_error()){
		$retVal["status"] = "Error";
		$retVal["msg"] = mysql_error()."<br />SQL was:"."INSERT INTO feeds (startID, endID, cacheimages, unreadOnChange, name, url, favicon) VALUES ({$r->endID}, {$r->endID}+1, '".mres($feed["cacheimages"])."', '".mres($feed["unreadOnChange"])."', '".mres($feed["name"])."', '".mres($feed["url"])."', '".mres($feed["favicon"])."')";
		my_mysql_query("ROLLBACK");
		return $retVal;
	}
	my_mysql_query("COMMIT");
	$q = my_mysql_query("SELECT * FROM feeds WHERE ID = LAST_INSERT_ID()");
	$retVal["feed"] = mysql_fetch_array($q);
	$retVal["status"] = "OK";
	$retVal["msg"] = "";
	return $retVal;
}/*}}}*/

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS);
mysql_select_db($MYSQL_DB);
my_mysql_query("SET NAMES 'utf8';");

$method = $_SERVER["REQUEST_METHOD"];
$path = preg_replace(";^/;", "", $_SERVER["PATH_INFO"]);
preg_match(";^(.*?)(\.(.*))?$;", $path, $match);
if (count($match) > 3){
	$format = $match[3];
} else {
	$format = "json";
}
$path = explode("/", $match[1]);

$data = Array();
$data["status"] = "error";
$data["msg"] = "Unknown query / method!";

switch ($path[0]){
	case "feeds": // {{{
		if ($method == "GET"){
			# GET /feeds
			$q = my_mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
			$data = Array();
			while ($r = mysql_fetch_object($q)){
				$data[] = $r;
			}
		}
		elseif ($method == "POST"){
			$feed = Array();
			$feed["url"] = $_REQUEST['url'];
			$feed["parent"] = $_REQUEST['parent'];
			$feed["cacheimages"] = $_REQUEST['cacheimages'];
			$feed["unreadOnChange"] = $_REQUEST['unreadOnChange'];
			$feed["name"] = $_REQUEST['name'];
			$data = addFeed($feed);
		}
		break;
		// }}}
	case "feed": // {{{
		if ($method == "GET"){{{
			# GET /feed/1
			$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			$r = mysql_fetch_object($q);
			if (count($path) < 3){
				$data = $r;
				break;
			}
			switch ($path[2]){
				# GET /feed/1/children
				case "children":
					if ("x".$r->url == "x"){
						$data = Array();
						$q = my_mysql_query("SELECT * FROM feeds WHERE startID > ".mres($r->startID)." AND endID < ".mres($r->endID)." ORDER BY startID ASC");
						while ($r = mysql_fetch_object($q)){
							$data[] = $r;
						}
					} else {
						header("Location: ../../unreadcount/".$r->ID);
					}
					break;
				# GET /feed/1/entries/2013-02-02
				case "entries":
					$data = Array();
					$date = "";
					$q = my_mysql_query("SELECT startID, endID FROM feeds WHERE ID = ".mres($r->ID));
					$r = mysql_fetch_object($q);
					if (count($path) >= 4){
						/* We need to get the first date with entries after one day before $path[4] */
						$q = my_mysql_query($SQL = "SELECT DISTINCT `date` FROM `entries`
							WHERE feedID IN (
								SELECT ID FROM feeds WHERE startID >= {$r->startID} AND endID <= {$r->endID}
							)
							AND `date` <= '".mres($path[3])." 23:59:59'
							ORDER BY `date` DESC
							LIMIT 1");
						if (!($d = mysql_fetch_object($q))){
							/* no more entries */
							break;
						}
						$date = "AND `date` >= '".substr($d->{"date"}, 0, 10)." 00:00:00' AND `date` < '".mres($path[3])." 23:59:59'";
					}
					$q = my_mysql_query($SQL = "SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE feedID IN (
						SELECT ID FROM feeds WHERE startID >= {$r->startID} AND endID <= {$r->endID}
					) $date ORDER BY `date` DESC");
					while ($r = mysql_fetch_object($q)){
						$r->title=htmlentities($r->title, ENT_NOQUOTES, "UTF-8");
						$data[] = $r;
					}
					break;
				# GET /feed/1/filter
				case "filter":
					$data = Array();
					$q = my_mysql_query("SELECT * FROM filter WHERE feedID = ".mres($r->ID)." ORDER BY ID ASC");
					while ($r = mysql_fetch_object($q)){
						$data[] = $r;
					}
					break;
			}
		}}}
		elseif ($method == "POST"){{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			switch ($path[2]){
				case "markAllRead":
					# POST /feed/1/markAllRead
					$maxID = "";
					if ($_POST["maxID"] > 0){
						$maxID = "AND ID <= ".mres($_POST["maxID"]);
					}
					$q = my_mysql_query("SELECT startID, endID FROM feeds WHERE ID = ".mres($path[1]));
					$feed = mysql_fetch_object($q);

					$q = my_mysql_query("UPDATE entries SET date=date, isread='1'
						WHERE feedID IN (SELECT ID FROM feeds WHERE startID >= {$feed->startID} AND endID <= {$feed->endID})
						AND isread='0' $maxID");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] = mysql_error();
						break;
					}
					$data["status"] = "OK";
					$data["msg"] = "";
					break;
				case "move":
					# POST /feed/1/move
					if (!array_key_exists("moveAfterFeed", $_POST) && !array_key_exists("moveBeforeFeed", $_POST) && !array_key_exists("moveIntoCategory", $_POST)){
						$data["status"] = "error";
						$data["msg"] = "Invalid query";
						break;
					}
					$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
					$feedToChange = mysql_fetch_object($q);
					$gap = ($feedToChange->endID - $feedToChange->startID) + 1;

					/* Take the feed we want to move OUT of the structure */
					my_mysql_query("UPDATE feeds SET startID = startID - {$feedToChange->startID}, endID = endID - {$feedToChange->startID}, movedirection = 'moveme' WHERE startID >= {$feedToChange->startID} AND endID <= {$feedToChange->endID}");

					/* Move everything AFTER it UPWARDS to close the gap */
					my_mysql_query("UPDATE feeds SET startID = startID - $gap WHERE startID > $feedToChange->endID AND movedirection = 'none'");
					my_mysql_query("UPDATE feeds SET endID = endID - $gap WHERE endID > $feedToChange->endID AND movedirection = 'none'");

					if (array_key_exists("moveAfterFeed", $_POST)){
						$q = my_mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($_POST["moveAfterFeed"])."'");
						$feedToMoveAfter = mysql_fetch_object($q);
						/* Make a gap for the feed we want to move */
						my_mysql_query("UPDATE feeds SET endID=endID+$gap WHERE endID > {$feedToMoveAfter->endID} AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET startID=startID+$gap WHERE startID >= {$feedToMoveAfter->endID} AND movedirection = 'none'"); # endID is correct!
						/* Move the feed to the newly created room */
						my_mysql_query("UPDATE feeds SET startID = startID + {$feedToMoveAfter->endID} + 1, endID = endID + {$feedToMoveAfter->endID} + 1, movedirection = 'none' WHERE movedirection = 'moveme'");
						$data["status"] = "OK";
						$data["msg"] = "Feed moved.";
					} elseif (array_key_exists("moveBeforeFeed", $_POST)){
						$q = my_mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($_POST["moveBeforeFeed"])."'");
						$feedToMoveBefore = mysql_fetch_object($q);
						/* Make a gap for the feed we want to move */
						my_mysql_query("UPDATE feeds SET endID=endID+$gap WHERE endID >= {$feedToMoveBefore->startID} AND movedirection = 'none'");
						my_mysql_query("UPDATE feeds SET startID=startID+$gap WHERE startID >= {$feedToMoveBefore->startID} AND movedirection = 'none'");
						/* Move the feed to the newly created room */
						my_mysql_query("UPDATE feeds SET startID = startID + {$feedToMoveBefore->startID}, endID = endID + {$feedToMoveAfter->endID}, movedirection = 'none' WHERE movedirection = 'moveme'");
						$data["status"] = "OK";
						$data["msg"] = "Feed moved.";
					} elseif (array_key_exists("moveIntoCategory", $_POST)){
						$q = my_mysql_query("SELECT * FROM feeds WHERE `ID` = '".mres($_POST["moveIntoCategory"])."'");
						$feedToMoveInto = mysql_fetch_object($q);
						/* Make a gap for the feed we want to move */
						my_mysql_query("UPDATE feeds SET endID=endID+$gap WHERE endID > {$feedToMoveInto->startID} AND movedirection = 'none'"); # startID is correct!
						my_mysql_query("UPDATE feeds SET startID=startID+$gap WHERE startID > {$feedToMoveInto->startID} AND movedirection = 'none'");
						/* Move the feed to the newly created room */
						my_mysql_query("UPDATE feeds SET startID = startID + {$feedToMoveInto->startID} + 1, endID = endID + {$feedToMoveInto->startID} + 1, movedirection = 'none' WHERE movedirection = 'moveme'");
						$data["status"] = "OK";
						$data["msg"] = "Feed moved.";
					}
					break;
			}
		}}}
		elseif ($method == "PUT"){ # {{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			# PUT /feed/1
			$UPDATE = "ID = ID";
			$update = json_decode(file_get_contents("php://input"));
			foreach (array("name", "url", "cacheimages", "unreadOnChange", "collapsed") as $k){
				if (property_exists($update, $k)){
					if ("".$update->$k != ""){
						$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
					}
				}
			}
			my_mysql_query("UPDATE feeds SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}
			foreach ($update->filter as $k => $v){
				if ($v->delete == "true"){
					my_mysql_query("DELETE FROM filter WHERE ID = ".mres($v->ID)." LIMIT 1");
				} else {
					if ($v->ID == "0" && strlen($v->regex) > 0){
						my_mysql_query("INSERT INTO filter (regex, whiteorblack, feedID) VALUES (\"".mres($v->regex)."\", \"".mres($v->whiteorblack)."\", ".mres($path[1]).")");
					} else {
						my_mysql_query("UPDATE filter SET regex = \"".mres($v->regex)."\", whiteorblack = \"".mres($v->whiteorblack)."\" WHERE ID = ".mres($v->ID)." LIMIT 1");
					}
				}
				if (mysql_errno()){
					$data["status"] = "error";
					$data["msg"] = mysql_error();
					break;
				}
			}
			$data["status"] = "OK";
			$data["msg"] = "";
		} # }}}
		elseif ($method == "DELETE"){{{
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			# DELETE /feed/1
			$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			$feed = mysql_fetch_object($q);
			my_mysql_query("DELETE FROM feeds WHERE ID = $feed->ID LIMIT 1");

			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			my_mysql_query("UPDATE feeds SET endID=endID-2 WHERE endID >= {$feed->endID}");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			my_mysql_query("UPDATE feeds SET startID=startID-2 WHERE startID >= $feed->endID"); # $feed->endID is correct!
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
				break;
			}

			$data["status"] = "OK";
			$data["msg"] = "";
		}}}
		break;
		// }}}
	case "entry": // {{{
		if ($method == "GET"){
			# GET /entry/1
			$data = Array();
			$q = my_mysql_query("SELECT * FROM entries WHERE ID = ".mres($path[1]));
			if ($r = mysql_fetch_object($q)){
				$data = $r;
				$q = my_mysql_query("SELECT * FROM tags WHERE ID IN (SELECT tagID FROM entries_tags WHERE entryID = {$r->ID})");
				$data->{"tags"} = Array();
				while ($r = mysql_fetch_object($q)){
					$data->{"tags"}[] = $r;
				}
			} else {
				$data["status"] = "error";
				$data["msg"] = "Unknown ID: ".mres($path[1]);
			}
		}
		elseif ($method == "PUT"){
			if ($ISDEMO){
				$data["status"] = "OK";
				$data["msg"] = "";
				break;
			}
			# PUT /entry/1
			if (count($path) == 2){
				$UPDATE = "date = date";
				$update = json_decode(file_get_contents("php://input"));
				foreach (array("isread", "favorite") as $k){
					if (property_exists($update, $k)){
						if ("".$update->$k != ""){
							$UPDATE .= ", `$k` = \"".mres($update->$k)."\"";
						}
					}
				}
				if ($UPDATE == "date = date"){
					$data["status"] = "error";
					$data["msg"] = "No valid data to update given!";
				} else {
					my_mysql_query("UPDATE entries SET $UPDATE WHERE ID = ".mres($path[1])." LIMIT 1");
					if (mysql_error() == ""){
						$data["status"] = "OK";
						$data["msg"] = "";
					} else {
						$data["status"] = "error";
						$data["msg"] = "Update failed: ".mysql_error();
					}
				}
			} elseif (count($path) == 3){
				# PUT /entry/1/tags
				$data["status"] = "OK";
				$data["msg"] = "";
				$tags = json_decode(file_get_contents("php://input"));
				foreach (explode(",", $tags->{"tags"}) as $t){
					my_mysql_query("INSERT IGNORE INTO tags (tag) VALUES (\"".mres($t)."\")");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] .= "Could not add tag '".mres($t)."': ".mysql_error();
						break;
					}
					my_mysql_query("INSERT INTO entries_tags (entryID, tagID) VALUES (".mres($path[1]).",
						(SELECT ID FROM tags WHERE tag = \"".mres($t)."\"))");
					if (mysql_error()){
						$data["status"] = "error";
						$data["msg"] .= "Could not add tag '".mres($t)."': ".mysql_error();
						break;
					}
				}
				break;
			}
		}
		elseif ($method == "DELETE"){
			if (count($path) == 4){
				# DELETE /entry/1/tags/foobar
				$data["status"] = "OK";
				$data["msg"] = "";
				my_mysql_query("DELETE FROM entries_tags WHERE entryID = \"".mres($path[1])."\" AND tagID = (SELECT ID FROM tags WHERE tag = \"".mres($path[3])."\")");
				my_mysql_query("DELETE FROM tags WHERE NOT EXISTS (SELECT ID FROM entries_tags WHERE tagID = tags.ID LIMIT 1)");
				break;
			}
		}
		break;
		// }}}
	case "unreadcount": // {{{
		# GET /unreadcount
		# GET /unreadcount/1
		if ($method == "GET"){
			$data = Array();
			$r = Array();
			if (count($path) >= 2){
				$q = my_mysql_query("SELECT * FROM feeds WHERE ID = ".mres($path[1]));
			} else {
				$q = my_mysql_query("SELECT * FROM feeds WHERE startID = 1");
			}
			$r = mysql_fetch_object($q);
			$qc = my_mysql_query("SELECT feeds.ID, feeds.startID, COUNT(entries.ID) AS unread, MAX(entries.ID) AS maxID
				FROM feeds
				LEFT JOIN entries ON entries.feedID IN (SELECT f.ID FROM feeds AS f WHERE f.startID >= feeds.startID AND f.endID <= feeds.endID) AND isread='0'
				WHERE feeds.startID >= ".$r->startID." AND feeds.endID <= ".$r->endID."
				GROUP BY feeds.ID");
			while ($qr = mysql_fetch_object($qc)){
				$data[] = $qr;
			}
		}
		break;
		// }}}
	case "favorites": // {{{
		# GET /favorites
		if (count($path) == 1){
			$data = Array();
			$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE favorite = 'yes' ORDER BY `date` DESC");
			while ($r = mysql_fetch_object($q)){
				$r->title=htmlentities($r->title, ENT_NOQUOTES, "UTF-8");
				$data[] = $r;
			}
			break;
		}
		else if (count($path) == 2){
			if ($path[1] == "count"){
				# GET /favorites/count
				$data = Array();
				$q = my_mysql_query("SELECT COUNT(*) AS num FROM entries WHERE favorite = 'yes'");
				while ($r = mysql_fetch_object($q)){
					$data["status"] = "OK";
					$data["msg"] = "";
					$data["num"] = $r->num;
				}
				break;
			}
		}
		break;
		// }}}
	case "unread": // {{{
		# GET /unread
		if (count($path) == 1){
			$data = Array();
			$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE isread = '0' ORDER BY `date` DESC");
			while ($r = mysql_fetch_object($q)){
				$r->title=htmlentities($r->title, ENT_NOQUOTES, "UTF-8");
				$data[] = $r;
			}
			break;
		}
		break;
		// }}}
	case "tags": // {{{
		if ($method == "GET"){
			# GET /tags
			if (count($path) == 1){
				$data["status"] = "OK";
				$data["msg"] = "";
				$data["tags"] = Array();
				$q = my_mysql_query("SELECT tags.*, COUNT(entries_tags.ID) AS num FROM tags LEFT JOIN entries_tags ON tagID = tags.ID GROUP BY entries_tags.tagID ORDER BY tag ASC");
				if (mysql_error()){
					$data["status"] = "error";
					$data["msg"] = "Unknown Error! ".mysql_error();
					break;
				}
				while ($r = mysql_fetch_object($q)){
					$data["tags"][] = $r;
				}
				break;
			}
			if (count($path) > 1){
				# GET /tags/1
				$data = Array();
				$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE ID IN (
					SELECT entryID FROM entries_tags WHERE tagID = ".mres($path[1])."
				) ORDER BY `date` DESC");
				while ($r = mysql_fetch_object($q)){
					$r->title=htmlentities($r->title, ENT_NOQUOTES, "UTF-8");
					$data[] = $r;
				}
				break;
			}
		}
		break;
		// }}}
	case "search": // {{{
		if ($method == "GET"){
			# GET /search/searchtext
			if (count($path) == 2){
				$data = Array();
				$q = my_mysql_query("SELECT ID, title, date, isread, feedID, favorite FROM entries WHERE
					title LIKE \"%".mres($path[1])."%\" OR description LIKE \"%".mres($path[1])."%\"
				ORDER BY `date` DESC LIMIT 100");
				if (mysql_error()){
					$data["status"] = "error";
					$data["msg"] = "Unknown Error! ".mysql_error();
					break;
				}
				while ($r = mysql_fetch_object($q)){
					$r->title=htmlentities($r->title, ENT_NOQUOTES, "UTF-8");
					$data[] = $r;
				}
				break;
			}
		}
		break;
		// }}}
	case "options": // {{{
		# GET /options
		if ($method == "GET"){
			$q = my_mysql_query("SELECT * FROM options");
			$data = Array();
			while ($r = mysql_fetch_object($q)){
				$data[$r->key] = $r;
			}
		}
		# POST /options/unreadOnChange
		elseif ($method == "POST"){
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			my_mysql_query("INSERT INTO options (`key`, `value`) VALUES ('".mres($path[1])."', '".mres($_POST["value"])."')
				ON DUPLICATE KEY UPDATE value=VALUES(value)");
			if (mysql_error()){
				$data["status"] = "error";
				$data["msg"] = mysql_error();
			}
			$data["status"] = "OK";
			$data["msg"] = "";
		}
		break;
		// }}}
	case "opml": // {{{
		if ($method == "POST"){/*{{{*/
			global $ISDEMO;
			if ($ISDEMO){
				$data["status"] = "error";
				$data["msg"] = "This is a demo :-)";
				break;
			}
			$parent = (int)$path[1];

			$body = file_get_contents($_FILES["file"]["tmp_name"]);
			$xml = new SimpleXMLElement($body);
			$outlines = $xml->xpath('/opml/body/outline');

			function recurse($outline, $parent){
				foreach($outline AS $k => $o){
					$feed = Array();
					$feed["type"] = "";
					foreach ($o->attributes() as $ak => $av){
						$ak = strtolower($ak);
						if ($ak == "title"){
							$feed["name"] = (string)$av;
						}
						if ($ak == "xmlurl"){
							$feed["url"] = (string)$av;
						}
						if ($ak == "type"){
							$feed["type"] = (string)$av;
						}
					}
					$feed["parent"] = $parent;
					$feed["cacheimages"] = 0;
					$feed["unreadOnChange"] = 1;
					if ($feed["type"] != "rss"){
						$feed["url"] = null;
						$feed["type"] = null;
						$r = addFeed($feed);
						error_log(var_export($r, true));
						$feed["children"] = recurse($o, $r["feed"]["ID"]);
					} else {
						$r = addFeed($feed);
					}
				}
			}

			$data["newFeeds"] = recurse($outlines, $parent);
		}/*}}}*/
		elseif ($method == "GET"){/*{{{*/
			# GET /opml
			$q = my_mysql_query("SELECT * FROM feeds ORDER BY startID ASC");
			$feeds = Array();
			while ($r = mysql_fetch_object($q)){
				$feeds[] = $r;
			}

			$data = Array();
			$data["head"] = Array("title" => "Subscriptions");
			$data["body"] = Array();

			function recurse(&$feeds, $parent){/*{{{*/
				$retVal = Array();
				while ($f = current($feeds)){
					if ($f->startID > $parent->endID)
						break;
					next($feeds);
					$f->name = htmlspecialchars($f->name);
					$f->url = htmlspecialchars($f->url);
					if ("x".$f->url == "x"){
						$n = Array("title" => $f->name, "text" => $f->name, "outline" => recurse($feeds, $f));
					} else {
						$n = Array("title" => $f->name, "text" => $f->name, "type" => "rss", "xmlUrl" => $f->url);
					}
					$retVal[] = $n;
				}
				return $retVal;
			}/*}}}*/

			reset($feeds);
			$data["body"]["outline"] = recurse($feeds, $feeds[0]);
		}/*}}}*/
		break;
		// }}}
}

switch ($format){
case "xml":
	header ("Content-Type: application/octet-stream;charset=utf8");
	foreach ($data as $k => $v){
		if (is_object($v))
			$data[$k] = get_object_vars($v);
	}
	if ($path[0] == "opml"){
		$data = array("opml" => array("version" => "2.0", $data));
		echo xml_encode($data);
	} else 
		echo xml_encode(array($path[0] => $data));
	break;
case "json":
default:
	header ("Content-Type: text/plain;charset=utf8");
	echo json_encode($data);
}
exit;
?>
