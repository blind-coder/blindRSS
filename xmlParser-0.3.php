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

/*
 *  Written by Reverend Jim (jim@revjim.net)
 * 
 *  http://revjim.net/code/xmlParser/
 */

class XMLParser {
	var $ns2uri = array();
	var $uri2ns = array();
	var $unkcnt = 0;
	var $data; // Holds the XML structure
	var $xmldata; // Holds XML data
	var $version = "0.3";


	function defineNs($ident, $uri = "") {
		if ($uri == "") {
			$uri = "::UNDEFINED::";
		}
		$this->ns2uri[strtoupper($ident)] = $uri;
		$this->uri2ns[$uri] = strtoupper($ident);
	}

	function _getXmlChildren(&$vals, $ns, &$i) {
		$children = array();

		if ($vals[$i]['value']) {
			array_push($children, $vals[$i]['value']);
		}
	
		while (++$i < count($vals)) {
			switch ($vals[$i]['type']) {
				case 'cdata':
					array_push($children, $vals[$i]['value']);
					break;
	
				case 'complete':
					$tmpns = $this->getnamespaces($vals[$i]['attributes'],$ns);
					$tag = $this->_convertTagNs($vals[$i]['tag'],$tmpns);
					if($vals[$i]['value']) {
						array_push($children, array(
							'tag' => $tag, 
							'attributes' => $vals[$i]['attributes'], 
							'children' => array($vals[$i]['value'])
						));
					} else {
						array_push($children, array(
							'tag' => $tag, 
							'attributes' => $vals[$i]['attributes'] 
						));
					}

					break;
	
				case 'open':
					$tmpns = $this->getnamespaces($vals[$i]['attributes'],$ns);
					$tag = $this->_convertTagNs($vals[$i]['tag'],$tmpns);
					array_push($children, array(
						'tag' => $tag, 
						'attributes' => $vals[$i]['attributes'], 
						'children' => $this->_getXmlChildren($vals,$tmpns,$i)
					));
					break;
	
				case 'close':
					if ($vals[$i]['value']) {
						array_push($children, $vals[$i]['value']);
					}
					return $children;
			}
		}
	}

	function _convertTagNs($tag,$ns) {
		if($pos = strpos($tag,':')) {
			$docns = substr($tag,0,$pos);
			$doctag = substr($tag,$pos+1);
		} else {
			$docns = "::ROOT";
			$doctag = "$tag";
		}

		if ($ns[$docns]) {
			$uri = $ns[$docns];
		} else {
			$uri = "::UNDEFINED::";
		}

		if($this->uri2ns[$uri]) {
			$parns = $this->uri2ns[$uri];
		} else {
			$this->definens("::UNK" . $this->unkcnt, $uri);
			$parns = "::UNK" . $this->unkcnt;
			$this->unkcnt++;
		}

		return $parns . ":" . $doctag;
		
	}

	function getXmlTree() {
		return $this->data;
	}

	function setXmlData($data) {
		$this->xmldata = $data;
	}

	function buildXmlTree() {
		$p = xml_parser_create();
		xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($p, $this->xmldata, &$vals, &$index);
		xml_parser_free($p);
	
		$this->data = array();
		$i = 0;
		$ns = $this->getnamespaces($vals[$i]['attributes']);
		array_push($this->data, array(
			'tag' => $this->_convertTagNs($vals[$i]['tag'],$ns), 
			'attributes' => $vals[$i]['attributes'],
			'children' => $this->_getXmlChildren($vals, $ns, $i)
		));
	}	 

	function getnamespaces($attribs,$ns = array()) {
		if (is_array($attribs)) {
			foreach($attribs as $key => $value) {
				$key = strtoupper($key);
				if (substr($key,0,5) == 'XMLNS') {
					if($pos = strpos($key,':')) {
						$ns[substr($key,$pos+1)] = $value;
					} else {
						$ns['::ROOT']= $value;
					}
				}
			}
		}


		return $ns;
	}
					

}
	
?>
