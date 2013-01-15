<?
/*
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/*  Written by Reverend Jim (jim@revjim.net)
 *
 *  http://revjim.net/code/feedParser/
 */


class feedParser {

	var $version = "0.5";
	var $entities = array(
		'nbsp' =>   "&#160;",
		'iexcl' =>  "&#161;",
		'cent' =>   "&#162;",
		'pound' =>  "&#163;",
		'curren' => "&#164;",
		'yen' =>    "&#165;",
		'brvbar' => "&#166;",
		'sect' =>   "&#167;",
		'uml' =>    "&#168;",
		'copy' =>   "&#169;",
		'ordf' =>   "&#170;",
		'laquo' =>  "&#171;",
		'not' =>    "&#172;",
		'shy' =>    "&#173;",
		'reg' =>    "&#174;",
		'macr' =>   "&#175;",
		'deg' =>    "&#176;",
		'plusmn' => "&#177;",
		'sup2' =>   "&#178;",
		'sup3' =>   "&#179;",
		'acute' =>  "&#180;",
		'micro' =>  "&#181;",
		'para' =>   "&#182;",
		'middot' => "&#183;",
		'cedil' =>  "&#184;",
		'sup1' =>   "&#185;",
		'ordm' =>   "&#186;",
		'raquo' =>  "&#187;",
		'frac14' => "&#188;",
		'frac12' => "&#189;",
		'frac34' => "&#190;",
		'iquest' => "&#191;",
		'Agrave' => "&#192;",
		'Aacute' => "&#193;",
		'Acirc' =>  "&#194;",
		'Atilde' => "&#195;",
		'Auml' =>   "&#196;",
		'Aring' =>  "&#197;",
		'AElig' =>  "&#198;",
		'Ccedil' => "&#199;",
		'Egrave' => "&#200;",
		'Eacute' => "&#201;",
		'Ecirc' =>  "&#202;",
		'Euml' =>   "&#203;",
		'Igrave' => "&#204;",
		'Iacute' => "&#205;",
		'Icirc' =>  "&#206;",
		'Iuml' =>   "&#207;",
		'ETH' =>    "&#208;",
		'Ntilde' => "&#209;",
		'Ograve' => "&#210;",
		'Oacute' => "&#211;",
		'Ocirc' =>  "&#212;",
		'Otilde' => "&#213;",
		'Ouml' =>   "&#214;",
		'times' =>  "&#215;",
		'Oslash' => "&#216;",
		'Ugrave' => "&#217;",
		'Uacute' => "&#218;",
		'Ucirc' =>  "&#219;",
		'Uuml' =>   "&#220;",
		'Yacute' => "&#221;",
		'THORN' =>  "&#222;",
		'szlig' =>  "&#223;",
		'agrave' => "&#224;",
		'aacute' => "&#225;",
		'acirc' =>  "&#226;",
		'atilde' => "&#227;",
		'auml' =>   "&#228;",
		'aring' =>  "&#229;",
		'aelig' =>  "&#230;",
		'ccedil' => "&#231;",
		'egrave' => "&#232;",
		'eacute' => "&#233;",
		'ecirc' =>  "&#234;",
		'euml' =>   "&#235;",
		'igrave' => "&#236;",
		'iacute' => "&#237;",
		'icirc' =>  "&#238;",
		'iuml' =>   "&#239;",
		'eth' =>    "&#240;",
		'ntilde' => "&#241;",
		'ograve' => "&#242;",
		'oacute' => "&#243;",
		'ocirc' =>  "&#244;",
		'otilde' => "&#245;",
		'ouml' =>   "&#246;",
		'divide' => "&#247;",
		'oslash' => "&#248;",
		'ugrave' => "&#249;",
		'uacute' => "&#250;",
		'ucirc' =>  "&#251;",
		'uuml' =>   "&#252;",
		'yacute' => "&#253;",
		'thorn' =>  "&#254;",
		'yuml' =>   "&#255;"
	);

	var $namespaces = array(
		'DC' => 'http://purl.org/dc/elements/1.1/',
		'RDF' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'RSS' => 'http://purl.org/rss/1.0/',
		'RSS2'=> 'http://backend.userland.com/rss2',
		'RDF2' => 'http://my.netscape.com/rdf/simple/0.9/'
	);

	function buildStruct($xmldata) {
		// Create a parser object
		$p = new XMLParser;
	
		// Define our known namespaces
		foreach ($this->namespaces as $space => $uri) {
			$p->definens($space,$uri);
		}
	
		// Define base namespace
		$p->definens("UNDEF");

		$this->parseEntities($xmldata);
	
		// Tell the parser to get the file.
		$p->setXmlData($xmldata);
	
		// Tell the parser to build the tree.
		$p->buildXmlTree();
	
		// Spit the tree out so we can see it
		return $p->getXmlTree();
	
	}

	function parseEntities(&$data) {

		foreach($this->entities as $entity => $replace) {
			$data = preg_replace('/&' . $entity . ';/',$replace,$data);
		}

		$data = preg_replace('/&[ ]*;/','',$data);

	}


	function parseFeed($xmldata) {
		$data =& $this->buildStruct(&$xmldata);
		if(is_array($data)) {
			foreach($data as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "RSS:RSS":
						case "UNDEF:RSS":
						case "RSS2:RSS":
							$info = $this->parseRSS(&$child);
							break;
						case "RDF:RDF":
							$info = $this->parseRDF(&$child);
							break;
						default:
							$info["warning"] .= "Unknown document format: " . $child['tag'] . "\n";
							break;
					}
				}
			}
		} 
		
		return $info;
	
	}

	function parseRDF(&$data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "RSS:CHANNEL":
						case "RDF2:CHANNEL":
							$channel = $this->getRDFChannel(&$child);
							break;
						case "RSS:ITEM":
						case "RDF2:ITEM":
							$item[] = $this->getRDFItem(&$child);
							break;
						default:
							break;
					}
				}
			}
		
		} 
	
		return array('channel' => $channel, 'item' => $item);
			
	}

	function parseRSS(&$data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "RSS:CHANNEL":
						case "RSS2:CHANNEL":
						case "UNDEF:CHANNEL":
							$info = $this->getRSSChannel(&$child);
							break;
						default:
							break;
					}
				}
			}
		
		} 
	
		return $info;
			
	}

	function getRDFChannel($data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "RSS:TITLE":
						case "RDF2:TITLE":
							$channel['title'] = $child['children'][0];
							break;
						case "RSS:LINK":
						case "RDF2:LINK":
							$channel['link'] = $child['children'][0];
							break;
						case "RSS:DESCRIPTION":
						case "RDF2:DESCRIPTION":
							$channel['description'] = $child['children'][0];
							break;
						case "RSS:WEBMASTER":
							$channel['creator'] = $child['children'][0];
							break;
						default:
							break;
					}
				}
			}
		}

		return $channel;
	}

	function getRSSChannel($data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "UNDEF:TITLE":
						case "RSS:TITLE":
						case "RSS2:TITLE":
							$channel['title'] = $child['children'][0];
							break;
						case "UNDEF:LINK":
						case "RSS:LINK":
						case "RSS2:LINK":
							$channel['link'] = $child['children'][0];
							break;
						case "UNDEF:DESCRIPTION":
						case "RSS:DESCRIPTION":
						case "RSS2:DESCRIPTION":
							$channel['description'] = $child['children'][0];
							break;
						case "UNDEF:ITEM":
						case "RSS:ITEM":
						case "RSS2:ITEM":
							$item[] = $this->getRSSItem(&$child);
							break;
						case "UNDEF:LASTBUILDDATE":
						case "RSS:LASTBUILDDATE":
						case "RSS2:LASTBUILDDATE":
							$channel['lastbuilddate'] = strtotime($child['children'][0]);
							break;
						default:
							break;
					}
				}
			}
		}
	
		return array('channel' => $channel, 'item' => $item);
	}

	function getRDFItem($data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "RSS:TITLE":
						case "RDF2:TITLE":
							$item['title'] = $child['children'][0];
							break;
						case "RSS:LINK":
						case "RDF2:LINK":
							$item['link'] = $child['children'][0];
							break;
						case "RSS:DESCRIPTION":
						case "RDF2:DESCRIPTION":
							$item["description"] = $child['children'][0];
							break;
						case "DC:DATE":
							$item["date"] = $this->dcDateToUnixTime($child['children'][0],0);
							$item["locdate"] = $this->dcDateToUnixTime($child['children'][0],1);
							break;
						default:
							break;
					}
				}
			}
		}
	
		return $item;
	}
	
	function getRSSItem($data) {
		if(is_array($data['children'])) {
			foreach($data['children'] as $child) {
				if(is_array($child)) {
					switch($child['tag']) {
						case "UNDEF:TITLE":
						case "RSS:TITLE":
						case "RSS2:TITLE":
							$item['title'] = $child['children'][0];
							break;
						case "UNDEF:LINK":
						case "RSS:LINK":
						case "RSS2:LINK":
							$item['link'] = $child['children'][0];
							break;
						case "UNDEF:DESCRIPTION":
						case "RSS:DESCRIPTION":
						case "RSS2:DESCRIPTION":
							$item["description"] = $child['children'][0];
							break;
						case "DC:DATE":
							$item["date"] = $this->dcDateToUnixTime($child['children'][0],0);
							$item["locdate"] = $this->dcDateToUnixTime($child['children'][0],1);
							break;
						case "UNDEF:PUBDATE":
						case "RSS:PUBDATE":
						case "RSS2:PUBDATE":
							$item["date"] = strtotime($child['children'][0]);
							$item["locdate"] = strtotime($child['children'][0]);
							break;
						default:
							break;
					}
				}
			}
		}
	
		return $item;
	}

	function dcDateToUnixTime($dcdate,$cvttz = 1) {
		list($date,$time) = explode("T",$dcdate);
		preg_match(
			"/([0-9]{2}:[0-9]{2}:[0-9]{2})(\-?\+?)([0-9]{2}):([0-9]{2})/",
			$time,
			$yo
		);

		if ($cvttz == 1) {
			return strtotime($date . " " . $yo[1] . $yo[2] . $yo[3] . $yo[4]);
		} else {
			return strtotime($date . " " . $yo[1]);
		}

	}
}
		
?>
