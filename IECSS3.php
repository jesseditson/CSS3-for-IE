<?php
/// A simple php class to fix IE styles on non-accepted CSS3 values, such as nth-child.
/// Uses jQuery or prototype to substitute styles when they aren't compatible.

/// This class was made with support for IE 7+ in mind, but takes into consideration some IE 6 rules.
/// Do not expect this script to fix sites in IE 6, but it will fix some things.

/// This class will only work with external stylesheets.
/// Inline styles will be overwritten when using this class.
/// Speech and media query related rules are not currently supported.
/// CSS functions are not currently supported.
/// rgba, hsl, and hsla colors are not currently supported.


/// This class currently supports most CSS3 Selectors, limited only by the javascript library it is used with.
/// If you find any selectors that don't work with this class, 
/// feel free to let me know, and I'll either add support or add a note to the documentation.

/// Microsoft has provided documentation on supported css selectors, it can be found here:
/// http://msdn.microsoft.com/en-us/library/cc351024(VS.85).aspx

/// Single line shorthand rules are not substituted, and may not work with some libraries.
/// Support for shorthand rules will be added in the future.

/// Rule substitution is not supported yet, but an array of unsupported or patially supported rules has been added for future support.

/// This script WILL add a bunch of code bloat to ie pages, but shouldn't effect performance much
/// on other browsers. You'd rather have the end user see the page slowly than incorrectly, right?

/// This script is provided with no warrantee, but if you find any issues, please email me.
/// ©2010 Jesse ditson (jesse.ditson@gmail.com), all rights reserved. 
/// You may use this script anywhere you like, as long as you do not remove or modify this signature.

class IECSS3 {

	private $currentStyles;
	private $root_path;
	private $library;
	private $libraries = array("jQuery","prototype");
	private $selectors = array("lang","first","left","right","root","nth-child","nth-last-child","nth-of-type","nth-last-of-type","last-child","first-of-type","last-of-type","only-child","only-of-type","empty","target","not","enabled","disabled","checked","indeterminate","default","valid","invalid","in-range","out-of-range","required","optional","read-only","read-write","after","before","first-letter","first-line","selection","value","choices","repeat-item","repeat-index");
	private $unsupported = array("hover");
//	private $rules = array("background-position","color-profile","rendering-intent","background","background-clip","background-origin","background-break","background-size","font-weight","white-space","word-spacing","font-effect","font-emphasize","font-size-adjust","font-smooth","font-stretch","hanging-punctuation","punctuation-trim","ruby-align","ruby-overhang","ruby-span","text-align-last","text-emphasis","text-justify","text-outline","text-overflow","text-shadow","text-wrap","word-break","word-wrap","writing-mode","content","counter-increment","counter-reset","quotes","border-collapse","border-spacing","border-style","caption-side","empty-cells","border-break","border-image","border-radius","box-shadow","bottom","display","left","overflow","position","right","top","z-index","orphans","page-break-inside","widows","fit","fit-position","image-orientation","page","size","outline","outline-color","outline-style","outline-width","appearance","box-sizing","icon","nav-down","nav-index","nav-left","nav-right","nav-up","outline-offset","outline-radius","resize","column-break-after","column-break-before","column-break-inside","column-count","column-gap","column-rule","columns","transparent","inherit","initial");
	
	public function IECSS3($lib=false){
		$this->currentStyles = array();
		($lib==false)?$this->set_library($this->libraries[0]):$this->set_library($lib);
		$this->styleScripts = array();
	}
	/// Set default
	public function set_library($libStr){
		if(in_array($libStr, $this->libraries)){
			$this->library = $libStr;
			return true;
		} else {
			$this->library = $this->libraries[0];
			return false;
		}
	}
	/// Set styles root
	public function set_root($path){
		// Set root to provided path, add trailing slash if needed.
		$this->root_path = (substr($path, -1)=='/'||$path=="")?$path:$path.'/';
	}
	/// Accepts either comma separated filenames, or an array of filenames
	public function add_styles($stylesString){
		// explode string if array not provided
		if(!is_array($stylesString)){
			if(strpos($stylesString, ',')===false){
				array_push($this->currentStyles, $stylesString);
			} else {
				$this->currentStyles = explode(',',$styleString);
			}
		} else {
			$this->currentStyles = array_unique(array_merge($this->currentStyles,$stylesString)); 
		}
		
		// trim all nodes
		$this->currentStyles = array_map('trim', $this->currentStyles);
	}
	/// Draws javascript - accepts an optional value to add styles with this method
	public function draw($styles=false){
		//if($this->browser_is_ie()){
			if($styles!=false){
				$this->add_styles($styles);
			}
			/// Draw!
			echo "<!-- begin IE Incompatible rule overrides - http://github.com/jesseditson/CSS3-for-IE -->\n";
			echo '<script type="text/javascript">'."\n";
			$js = "";
			foreach($this->currentStyles as $style){
				ob_start();
				include $this->root_path.$style;
				$stylesheet = ob_get_clean();
				$js .= $this->parse_css($this->remove_comments($stylesheet));
			}
			$this->draw_js_wrapper($js, $this->library);
			echo '</script>';
			echo "<!-- end IE Incompatible rule overrides -->\n";
			
		//}
	}
	
	/// Start the processing for our css
	private function parse_css($raw){
		$css = $this->styles_to_array($raw);
		return $this->convert_to_js($css, $this->library);
	}
	
	/// Drawing methods
	private function draw_js_wrapper($contents, $library){
		switch($library){
			case 'jQuery':
				$jsString = "$(document).ready(function(){\n".$contents."});";
				break;
			case 'prototype':
				$jsString = "document.observe(\"dom:loaded\", function() {\n".$contents."});";
				break;
		}
		echo $jsString;
	}
	/// Shortcode fixes:
	private function parse_shortcodes($rules){
		$newRules = array();
		//print_r($rules);
		if(is_array($rules)){
			foreach($rules as $rule=>$value){
				$newRules = $this->replace_shortcode($rule, $value, $newRules);
			}
		}
		//print_r($newRules);
		return $newRules;
	}
	private function explode_shortcode($val){
		/// need to add handling here for pairs (top/left, font families)
		$ret = explode(' ', $val);
		return (count($ret)>0)?$ret:array($val);
	}
	private function replace_shortcode($rule, $value, $array){
		$valueArr = $this->explode_shortcode($value);
		switch($rule){
			case 'background':
				for($v=0;$v<count($valueArr);$v++){
					$val = $valueArr[$v];
					if($this->regex_rule($val, 'repeat')){
						$array['background-repeat'] = $val;
					} else if($this->regex_rule($val, 'url')){
						$array['background-image'] = $val;
					} else if($this->regex_rule($val, 'color')){
						$array['background-color'] = $val;
					} else if($this->regex_rule($val, 'position')){
						$array['background-position'] = $val;
					}
				}
				break;
			case 'border':
				for($v=0;$v<count($valueArr);$v++){
					$val = $valueArr[$v];
					if($this->regex_rule($val, 'color')){
						$array['border-color'] = $val;
					} else if($this->regex_rule($val, 'border-style')){
						$array['border-style'] = $val;
					} else if($this->regex_rule($val, 'position')){
						$array['border-width'] = $val;
					}
				}
				break;
			case 'font':
				for($v=0;$v<count($valueArr);$v++){
					$val = $valueArr[$v];
					if($this->regex_rule($val, 'font-style')){
						$array['font-style'] = $val;
					} else if($this->regex_rule($val, 'font-variant')){
						$array['font-variant'] = $val;
					} else if($this->regex_rule($val, 'font-weight')){
						$array['font-weight'] = $val;
					} else if($this->regex_rule($val, 'color')){
						$array['color'] = $val;
					} else if($this->regex_rule($value, 'font-family')){
						$nVal = $this->regex_rule($value, 'font-family');
						$array['font-family'] = $nVal[0];
					}
					if($this->regex_rule($val, 'font-size')){
						$nVal = $this->regex_rule($val, 'font-size');
						$array['font-size'] = $nVal[0];
						if(isset($nVal[1])) $array['line-height'] = $nVal[1];
					}
				}
				break;
			case 'margin':
				if(count($valueArr)==1){
					$array['margin'] = $value;
				} else if(count($valueArr)==2){
					$array['margin-top'] = $valueArr[0];
					$array['margin-right'] = $valueArr[1];
					$array['margin-bottom'] = $valueArr[0];
					$array['margin-left'] = $valueArr[1];
				} else if(count($valueArr)==3){
					$array['margin-top'] = $valueArr[0];
					$array['margin-right'] = $valueArr[1];
					$array['margin-bottom'] = $valueArr[2];
					$array['margin-left'] = $valueArr[1];
				} else if(count($valueArr)==4){
					$array['margin-top'] = $valueArr[0];
					$array['margin-right'] = $valueArr[1];
					$array['margin-bottom'] = $valueArr[2];
					$array['margin-left'] = $valueArr[3];
				}
				break;
			case 'padding':
				if(count($valueArr)==1){
					$array['padding'] = $value;
				} else if(count($valueArr)==2){
					$array['padding-top'] = $valueArr[0];
					$array['padding-right'] = $valueArr[1];
					$array['padding-bottom'] = $valueArr[0];
					$array['padding-left'] = $valueArr[1];
				} else if(count($valueArr)==3){
					$array['padding-top'] = $valueArr[0];
					$array['padding-right'] = $valueArr[1];
					$array['padding-bottom'] = $valueArr[2];
					$array['padding-left'] = $valueArr[1];
				} else if(count($valueArr)==4){
					$array['padding-top'] = $valueArr[0];
					$array['padding-right'] = $valueArr[1];
					$array['padding-bottom'] = $valueArr[2];
					$array['padding-left'] = $valueArr[3];
				}
				break;
			default :
				$array[$rule] = $value;
				break;
		}
		return $array;
	}
	/// Rule regexes
	private function regex_rule($r, $v){
		$matched = false;
		$matches = array();
		$p='';
		switch($v){
			case 'color':
				$p = '/(#?[1-9a-f^\(^\ ]{3,6}|[a-z^\(^\ ]+[\(]?[0-9,]{11}?[\)]?)/i';
				break;
			case 'url':
				$p = '/(url\(.+\))/i';
				break;
			case 'repeat':
				$p = '/([[no-]{0,3}[repeat]{6}[-xy]{0,2})/i';
				break;
			case 'position':
				$p = '/([0-9]+[pxemtcin%]{1,2}|[lefrightopbmcn]{1,6})/';
				break;
			case 'border-style':
				$styles = array('none','hidden','dotted','solid','double','groove','ridge','inset','outset');
				if(in_array(strtolower($r), $styles)) $matched = $r;
				break;
			case 'font-style':
				$styles = array('italic', 'oblique');
				if(in_array(strtolower($r), $styles)) $matched = $r;
				break;
			case 'font-variant':
				$styles = array('small-caps');
				if(in_array(strtolower($r), $styles)) $matched = $r;
				break;
			case 'font-weight':
				$styles = array('bold','bolder','lighter');
				if(in_array(strtolower($r), $styles)){
					$matched = $r;
				} else {
					$p = '/([1-9]{1}[0]{2})/';
				}
				break;
			case 'font-family':
				// NOTE: PASS ENTIRE RULE IN FOR THIS TO WORK
				$p = '/([\ ]["\']?[a-z\ "\',]*$)/i';
				break;
			case 'font-size':
				$p = '/([0-9]+[pxemtcin%]{1,2})/';
				break;
		}
		if(strlen($p) > 0) preg_match_all($p, $r, $matches);
		if(isset($matches[1])){
			if(count($matches[1]) == 1){
				return $matches[1];
			} else if($matched != false){
				return $matched;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/// Most of the conversion is done here.
	private function convert_to_js($css, $library){
		$jsString = "";
		foreach($css as $selector=>$rules){
			if($this->validate_selector($selector)){
				$rules = $this->parse_shortcodes($rules);
				if(is_array($rules) && count($rules) > 0){
					// begin the script
					$jsString .= "try{";
					switch($library){
						case 'jQuery':
							$jsString .= '$("'.$this->escape_meta_jquery($selector).'").css({';
							break;
						case 'prototype':
							$jsString .= '$$("'.$this->escape_meta_prototype($selector).'").collect(function(el){';
							$jsString .= "el.setStyle({";
							break;
					}
					$ruleNum = 0;
					foreach($rules as $rule=>$value){
						$ruleNum ++;
						// add each rule
						switch($library){
							case 'jQuery':
								$jsString .= "'".$rule."':'".$this->escape_quotes($value)."'";
								$jsString .= ($ruleNum<count($rules))?',':'';
								break;
							case 'prototype':
								$jsString .= "'".$rule."':'".$this->escape_quotes($value)."'";
								$jsString .= ($ruleNum<count($rules))?',':'';
								break;
						}
					}
					// end the script
					switch($library){
						case 'jQuery':
							$jsString .= "});";
							break;
						case 'prototype':
							$jsString .= "});});";
							break;
					}
					$jsString .= "};catch(err){/*console.log('CSS Rule Error for ".$selector.": '+err.description);*/}\n";
				}
			}
		}
		return $jsString;
	}
	
	/// Parsing methods
	private function validate_selector($selector){
		$v = false;
		foreach($this->selectors as $s){
			if(stristr($selector, $s)!==false){
				$v = true;
			}
		}
		foreach($this->unsupported as $s){
			if(stristr($selector, $s)){
				$v = false;
			}
		}
		return $v;
	}
	private function remove_comments($string){
		$string = str_replace("\r", "", $string);
		$string = str_replace("\n", "", $string);
		$pattern = '/\/\*(.+?)\*\//';
		return preg_replace($pattern, '', $string);
	}
	private function styles_to_array($css){
		$styles = array();
		$rows = explode('}', $css);
		foreach($rows as $row){
			$selectors = $this->even_keys(explode('{',$row));
			$rules = $this->odd_keys(explode('{',$row));
			foreach($selectors as $key=>$selector){
				if($this->validate_selector($selector)){
					$styles[trim($selector)] = $this->rules_to_array($rules[$key]);
				}
			}
		}
		return $styles;
	}
	private function rules_to_array($css){
		$pattern = '/(.+?):(.+?);/i';
		preg_match_all($pattern, $css, $rules);
		return @array_combine(array_map('trim',$rules[1]), array_map('trim',$rules[2]));
	}
	
	/// Utilities
	private function browser_is_ie(){
		///$known = array('msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko'); 
		//preg_match_all( '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#', strtolower( $_SERVER[ 'HTTP_USER_AGENT' ]), $browser ); 
		//if($browser['browser'][0]=='msie')
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			preg_match_all('/(MSIE)/i', $_SERVER['HTTP_USER_AGENT'], $browser);
		}
		
		if(strtolower(@$browser[0][0])=='msie'){
			return true; 
		} else {
			return false;
		}
	}
	private function escape_quotes($string){
		return str_replace("'", "\'", $string);
	}
	private function escape_meta_jquery($string){
		$pattern = '/([#|;|&|\,|\.|\+|\*|~|:|"|!|\^|\$|\[|\]|\(|\)|=|>|\||\/])/';
		
		return $string;
		//return preg_replace($pattern, "\\\\\\\\$0", $string);
	}
	private function escape_meta_prototype($string){
		return $string;
	}
	
	private function odd_keys($arr){
		$ret = array();
		foreach($arr as $k=>$v){
			if($k & 1){
	    		array_push($ret, $v);
	    	}
	    }
	    return $ret;
	}
	private function even_keys($arr){
		$ret = array();
		foreach($arr as $k=>$v){
			if(!($k & 1)){
	    		array_push($ret, $v);
	    	}
	    }
	    return $ret;
	}
}

?>