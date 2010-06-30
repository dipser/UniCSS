<?php

// UniCSS with Minifying
class UniCSS
{
	public $css = '';	
	private $vars = array();
	
	// Constructor
	public function __construct($file_unicss, $file_cachecss, $minify=TRUE, $force=FALSE)
	{
		
		// $file_unicss-Changedate newer than $file_cachecss
		// OR $newfile does not exist
		// OR $force=TRUE, force to generate a new cachefile
		if( $force || !file_exists($file_cachecss) || filemtime($file_unicss)>filemtime($file_cachecss) )
		{
			$unicss = file_get_contents($file_unicss); // READING...
			
			$this->css = trim($unicss);
			
			if($minify)
			{
				$this->css = preg_replace("/\/\*(.*)?\*\//Usi", '', $this->css); // Remove CSS comments
			}
			
			// Read variables: $var:value;
			$this->css = preg_replace_callback('/(\$[a-zA-Z0-9_]+)\s*:(.+);+[\s*]/Usi', array(&$this, 'replace_vars'), $this->css);

			// Replace variables with value
			ksort($this->vars);
			$this->vars = array_reverse($this->vars, true);
			foreach ($this->vars as $key => $val) {
				$this->css = str_replace($key, $val, $this->css);
			}
			
			
			
			
			// Function: @font-face(name, name.eot, name.ttf, [name.woff,...]);
			// Result: @font-face { font-family: ''; src: url('.eot'); src: local('☺'), url('.woff') format('woff'), url('.ttf') format('truetype'); }
			$this->css = preg_replace_callback('/@font-face\s*\((.+)\);/Usi', array(&$this, 'replace_fontface'), $this->css);

			// Function: @opacity:0.5; wird zu opacity:0.5;-moz-opacity:0.5;...
			$this->css = preg_replace_callback('/@opacity\s*:(.+);/Usi', create_function('$hit', 'return "-moz-opacity:$hit[1];-khtml-opacity:$hit[1];-ms-filter:\"alpha(opacity=".($hit[1]*100).")\";filter:alpha(opacity=".($hit[1]*100).");opacity:$hit[1];";'), $this->css);
			
			// Function: @box-shadow: * ;
			$this->css = preg_replace_callback('/@box-shadow\s*:(.+);/Usi', create_function('$hit', 'return "-moz-box-shadow:$hit[1];-webkit-box-shadow:$hit[1];box-shadow:$hit[1];";'), $this->css);
			
			// Function: @border-radius: * ;
			$this->css = preg_replace_callback('/@border-radius\s*:(.+);/Usi', create_function('$hit', 'return "-moz-border-radius:$hit[1];-webkit-border-radius:$hit[1];border-radius:$hit[1];";'), $this->css);
		
			// Function: @transition: all 0.3s ease-out;
			$this->css = preg_replace_callback('/@transition\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-transition:$hit[1];-moz-transition:$hit[1];-o-transition:$hit[1];";'), $this->css);
		
			// Function: @transform:rotate(7.5deg);
			$this->css = preg_replace_callback('/@transform\s*:\s*rotate\((.+)\)\s*;/Usi', create_function('$hit', '$h=$hit[1];$deg = (float) $h;$m21 = $h*M_PI/180;$m22=cos($m21);$ie6ie7 = "progid:DXImageTransform.Microsoft.Matrix(sizingMethod=\'auto expand\', M11=$m22, M12=-$m21, M21=$m21, M22=$m22)";$ie8 = "\\"progid:DXImageTransform.Microsoft.Matrix(sizingMethod=\'auto expand\', M11=$m22, M12=-$m21, M21=$m21, M22=$m22)\\"";return "-webkit-transform:rotate($hit[1]);-moz-transform:rotate($hit[1]);transform:rotate($hit[1]);filter:$ie6ie7;-ms-filter:$ie8;zoom:1;";'), $this->css);
		
			// Function: @transform: * ;    * could be scale(1.05)
			$this->css = preg_replace_callback('/@transform\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-transform:$hit[1];-moz-transform:$hit[1];transform:$hit[1];";'), $this->css);

			// Function: @box-sizing:content-box;
			$this->css = preg_replace_callback('/@box-sizing\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-box-sizing:$hit[1];-moz-box-sizing:$hit[1];-ms-box-sizing:$hit[1];box-sizing:$hit[1];";'), $this->css);
		
			// Function: @column-count: 2;
			$this->css = preg_replace_callback('/@column-count\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-column-count:$hit[1];-moz-column-count:$hit[1];column-count:$hit[1];";'), $this->css);
			// Function: @column-gap: 10px; 
			$this->css = preg_replace_callback('/@column-gap\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-column-gap:$hit[1];-moz-column-gap:$hit[1];column-gap:$hit[1];";'), $this->css);		
			
			// Function: @rgba();
			$this->css = preg_replace_callback('/(background-color)\s*:\s*@rgba\((.+)\)\s*;/Usi', array(&$this, 'replace_rgba'), $this->css);
		
			// Function: @rgb2hex()
			$this->css = preg_replace_callback('/([\w-@]+)\s*:([\s.]*)@rgb2hex\((.+)\)([\s.]*);/Usi', array(&$this, 'replace_rgb2hex'), $this->css);
		
			// Function: @hex2rgb()
			$this->css = preg_replace_callback('/@hex2rgb\((.+)\)/Usi', array(&$this, 'replace_hex2rgb'), $this->css);
		
			// Function: @base64()
			$this->css = preg_replace_callback('/@base64\((.+)\)/Usi', array(&$this, 'replace_base64'), $this->css);
		
			// Function: @math()
			$this->css = preg_replace_callback('/@math\((.+)\)/Usi', create_function('$hit', '$m=$hit[1];$m=str_replace("[", "(", str_replace("]", ")", $m));eval("\$m = $m;");return $m;'), $this->css); // Perhaps with a whitlist with all math functions!?
		
			// Function: @sprite({image1, image2,...}, spriteimagename, [vertical or horizontal])


			// Minify
			if($minify)
			{
				// Remove spaces, tabs, linebreaks,...
				$this->css = preg_replace("/\\s+/si", ' ', $this->css);
				$this->css = str_replace( ' {', '{', $this->css );
			
				// Remove the spaces after the things that should not have spaces after them.
				$this->css = preg_replace("/([!{}:;>+\\(\\[,])\\s+/si", "$1", $this->css);
				// Replace 0(px,em,%) with 0.
				$this->css = preg_replace("/([\\s:])(0)(px|em|%|in|cm|mm|pc|pt|ex)/is", "$1$2", $this->css);
			}
		
			$fp = fopen($file_cachecss, "w+");
	 		fwrite($fp, $this->css);
			fclose($fp);

			return $this->css;
		
		} // End-IF
		
		return false;
		
	} // End-Constructor
	

	
	// Replace Variables
	private function replace_vars($hit)
	{
		$this->vars[trim($hit[1])] = trim($hit[2]);
		return '';
	}
	
	
	// Replace @font-face
	private function replace_fontface($hit)
	{
		$h = $hit[1];
		$h = explode(",", $h);
		$name = trim($h[0]);
		$eot = trim($h[1]);
		$ttf = trim($h[2]);
		$rest = "";
		for($i=3; $i<count($h); $i++) {
			$url = trim($h[$i]);
			$format = $this->get_fontformat($h[$i]);
			$rest .= " url('$url') format('$format'),";
		}
		return "@font-face { font-family: '$name'; src: url('$eot'); src: local('☺'),$rest url('$ttf') format('truetype'); }";
	}
	// Get Font-Format
	private function get_fontformat($filename)
	{
		preg_match("/\.([a-z0-9]{2,4})(\?.*|#.*)?$/i", $filename, $fileSuffix);
		$ending = strtolower($fileSuffix[1]);
		if($ending=="woff") { return 'woff'; }
		elseif($ending=="otf") { return 'opentype'; }
		elseif($ending=="svg") { return 'svg'; }
		else { return ''; }
	}
	
	// Replace @rgba
	private function replace_rgba($hit)
	{
		$rgba = explode(",", $hit[2]);
		$r = $rgba[0];
		$g = $rgba[1];
		$b = $rgba[2];
		$a = $rgba[3];
		$hex = $this->get_int2hex($r).$this->get_int2hex($g).$this->get_int2hex($b);
		$mshex = $this->get_int2hex($a*255);
		return "$hit[1]:#$hex;$hit[1]:rgba($hit[2]);filter:progid:DXImageTransform.Microsoft.gradient(startColorStr='#$mshex$hex',EndColorStr='#$mshex$hex');-ms-filter:\"progid:DXImageTransform.Microsoft.gradient(startColorStr='#$mshex$hex',EndColorStr='#$mshex$hex')\";";
	}
	// Get: int2hex
	private function get_int2hex($int)
	{
		$i = intval($int);
		$i = dechex($i<0?0:($i>255?255:$i));
		$hex = (strlen($i) < 2?"0":"").$i;
		return $hex;
	}
	
	// Replace @rgb2hex
	private function replace_rgb2hex($hit)
	{
		$rgb = explode(',', $hit[3]);
		$r = $rgb[0];
		$g = $rgb[1];
		$b = $rgb[2];
		$hex = $this->get_int2hex($r).$this->get_int2hex($g).$this->get_int2hex($b);
		return "$hit[1]:$hit[2]#$hex$hit[4];";
	}
	
	// Replace @hex2rgb
	private function replace_hex2rgb($hit)
	{
		$rgb = $this->get_hex2rgb($hit[1]);
		return "rgb($rgb[0], $rgb[1], $rgb[2])";
	}
	// Get: int2hex
	private function get_hex2rgb($color)
	{
		if($color[0]=="#"){$color=substr($color,1);}
		if(strlen($color)==6){list($r,$g,$b)=array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);}
		elseif(strlen($color)==3){list($r,$g,$b)=array($color[0].$color[0],$color[1].$color[1],$color[2].$color[2]);}
		else{return false;}$r=hexdec($r);$g=hexdec($g);$b=hexdec($b);
		return array($r,$g,$b);
	}
	
	// Replace @base64
	private function replace_base64($hit)
	{
		return "data:".$this->get_mimetype($hit[1]).";base64,".base64_encode(implode("", file($hit[1])));
	}
	// Get Mimetype
	private function get_mimetype($filename)
	{
		preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
		switch(strtolower($fileSuffix[1])) {
			case "jpg": case "jpeg": case "jpe": return "image/jpg";
			case "png": case "gif": case "bmp": case "tiff": return "image/".strtolower($fileSuffix[1]);
			case "css": return "text/css";
			case "pdf": return "application/pdf";
			default: if(function_exists("mime_content_type")){$fileSuffix = mime_content_type($filename);}
			return "unknown/".trim($fileSuffix[0], ".");
		}
	}
	
}




// UniCSS call...
//
// Use: $css = new UniCSS('style.css', 'style.min.css');
//
// For this Example-Page:
$css = new UniCSS('style.css', 'style.min.css', FALSE, TRUE); // No minifying
$cssmin = new UniCSS('style.css', 'style.min.css', TRUE, TRUE); // With minifying

?>

<!DOCTYPE html>
<html>
 <head>
  <title>UniCSS</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link type="text/css" href="style.min.css" rel="stylesheet" /><!-- Here is the normal output of the generated CSS -->
 </head>
 <body>
 
  <!-- Example-Output -->
  <div id="#example">
   <h1>UniCSS Example</h1>
   <p>Unified CSS. Write CSS with no redundancy and no browser-vendor prefix. One lightweight PHP class with CSS minifying AND with caching of course.</p>
  </div>
  
  <!-- Unified CSS -->
  <div style="display:none;">
   <h3>UnifiedCSS:</h3>
   <pre style="border:10px solid darkgrey;"><?php echo file_get_contents('style.css'); ?></pre>
  </div>
  
  <!-- Resulting CSS (without minifying) -->
  <div style="display:none;">
   <h3>Resulting CSS: (<?php echo strlen($css->css); ?> Chars)</h3>
   <pre style="border:10px solid darkgreen;overflow:auto;"><?php echo $css->css; ?></pre>
  </div>
  
  <!-- Resulting CSS (with minifying) -->
  <div style="display:none;">
   <h3>Resulting CSS: (<?php echo strlen($cssmin->css); ?> Chars -- Reduced: <?php echo round((strlen($css->css)-strlen($cssmin->css))/strlen($css->css)*100); ?>%)</h3>
   <pre style="border:10px solid darkgreen;overflow:auto;max-height:200px;white-space:normal;"><?php echo $cssmin->css; ?></pre>
  </div>
  
  
  
  <!-- Contact... -->
  <div style="text-align:center;"><a href="http://github.com/dipser/UniCSS">Source on Github...</a></div>
  <div style="text-align:center;"><a href="http://www.twitter.com/dipser">Follow me or contact me on Twitter...</a></div>
  
  </div>
 </body>
</html>