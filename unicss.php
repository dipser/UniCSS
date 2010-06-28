<?php

// UniCSS with Minifying
function css_unify($file, $newfile, $minify=TRUE, $make=NULL)
{
	// $file-Datumstempel neuer als $newfile-Datumstempel
	// ODER $newfile-Datei existiert nicht
	// ODER $make==TRUE, d.h. es wird eine neue Generierung erzwungen
	if((filemtime($file)>filemtime($newfile) || !file_exists($newfile)) || $make==TRUE)
	{
		$unicss = file_get_contents($file);
		
		// Generate normal CSS from UniCSS
		$css = trim($unicss);
		if($minify) {
			$css = preg_replace("/\/\*(.*)?\*\//Usi", '', $css); // Remove Comments: /* */
		}
		
		// Variablen einlesen: $var:value;
		global $variables; // Variablen-Array
		$css = preg_replace_callback('/(\$[a-zA-Z0-9_-]+)\s*:(.+);+[\s*]/Usi', create_function('$hit', 'global $variables;$variables[] = array(trim($hit[1]), trim($hit[2]));return "";'), $css, -1, $count);

		// Variablen ersetzen
		for($i=0; $i<$count; $i++) {
			$css = preg_replace("/\\".$variables[$i][0].'/', $variables[$i][1], $css);
		}
		$css = trim($css);
		
		
		// Funktionen: @opacity:0.5; wird zu opacity:0.5;-moz-opacity:0.5;...
		$css = preg_replace_callback('/@opacity\s*:(.+);/Usi', create_function('$hit', 'return "-moz-opacity:$hit[1];-khtml-opacity:$hit[1];-ms-filter:\"alpha(opacity=".($hit[1]*100).")\";filter:alpha(opacity=".($hit[1]*100).");opacity:$hit[1];";'), $css);
		// Funktionen: @box-shadow
		$css = preg_replace_callback('/@box-shadow\s*:(.+);/Usi', create_function('$hit', 'return "-moz-box-shadow:$hit[1];-webkit-box-shadow:$hit[1];box-shadow:$hit[1];";'), $css);
		// Funktionen: @border-radius
		$css = preg_replace_callback('/@border-radius\s*:(.+);/Usi', create_function('$hit', 'return "-moz-border-radius:$hit[1];-webkit-border-radius:$hit[1];border-radius:$hit[1];";'), $css);
		// Funktionen: @transition: all 0.3s ease-out;
		$css = preg_replace_callback('/@transition\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-transition:$hit[1];-moz-transition:$hit[1];-o-transition:$hit[1];";'), $css);
		// Funktionen: @transform: scale(1.05);
		$css = preg_replace_callback('/@transform\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-transform:$hit[1];-moz-transform:$hit[1];transform:$hit[1];";'), $css);
		// Funktionen: @box-sizing:content-box;
		$css = preg_replace_callback('/@box-sizing\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-box-sizing:$hit[1];-moz-box-sizing:$hit[1];-ms-box-sizing:$hit[1];box-sizing:$hit[1];";'), $css);
		
		// Funktionen: @box-rotate:rotate(7,5deg);
		//$css = preg_replace_callback('/@box-rotate\s*:(.+);/Usi', create_function('$hit', 'return "-webkit-box-sizing:$hit[1];-moz-box-sizing:$hit[1];-ms-box-sizing:$hit[1];box-sizing:$hit[1];";'), $css);
		
		// Funktionen: @rgba();
		global $int2hex;
		$int2hex = create_function('$int', '$i=intval($int);$i=dechex($i<0?0:($i>255?255:$i));$hex=(strlen($i) < 2?"0":"").$i;return $hex;');
		
		$css = preg_replace_callback('/(background-color)\s*:\s*@rgba\((.+)\)\s*;/Usi', create_function('$hit', 'global $int2hex;$rgba=explode(",", $hit[2]);$r=$rgba[0];$g=$rgba[1];$b=$rgba[2];$a=$rgba[3];$hex=$int2hex($r).$int2hex($g).$int2hex($b); $mshex=$int2hex($a*255); return "$hit[1]:#$hex;$hit[1]:rgba($hit[2]);filter:progid:DXImageTransform.Microsoft.gradient(startColorStr=\'#$mshex$hex\',EndColorStr=\'#$mshex$hex\');-ms-filter:\\"progid:DXImageTransform.Microsoft.gradient(startColorStr=\'#$mshex$hex\',EndColorStr=\'#$mshex$hex\')\\";";'), $css);
		
		$css = preg_replace_callback('/([\w-@]+)\s*:([\s.]*)@rgb2hex\((.+)\)([\s.]*);/Usi', create_function('$hit', 'global $int2hex;$rgb=explode(",", $hit[3]);$r=$rgb[0];$g=$rgb[1];$b=$rgb[2];$hex=$int2hex($r).$int2hex($g).$int2hex($b); return "$hit[1]:$hit[2]#$hex$hit[4];";'), $css);
		
		// Function: @hex2rgb()
		global $hex2rgb;
		$hex2rgb = create_function('$color', 'if($color[0]=="#"){$color=substr($color,1);}if(strlen($color)==6){list($r,$g,$b)=array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);}elseif(strlen($color)==3){list($r,$g,$b)=array($color[0].$color[0],$color[1].$color[1],$color[2].$color[2]);}else{return false;}$r=hexdec($r);$g=hexdec($g);$b=hexdec($b);return array($r,$g,$b);');
		
		$css = preg_replace_callback('/@hex2rgb\((.+)\)/Usi', create_function('$hit', 'global $hex2rgb;$rgb=$hex2rgb($hit[1]);return "rgb($rgb[0], $rgb[1], $rgb[2])";'), $css);

		// Function: @base64()
		global $mimetype;
		$mimetype = create_function('$filename', 'preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);switch(strtolower($fileSuffix[1])){case "jpg": case "jpeg": case "jpe": return "image/jpg"; case "png": case "gif": case "bmp": case "tiff": return "image/".strtolower($fileSuffix[1]); case "css": return "text/css"; case "pdf": return "application/pdf"; default: if(function_exists("mime_content_type")){$fileSuffix = mime_content_type($filename);} return "unknown/".trim($fileSuffix[0], ".");}');

		$css = preg_replace_callback('/@base64\((.+)\)/Usi', create_function('$hit', 'global $mimetype;return "data:".$mimetype($hit[1]).";base64,".base64_encode(implode("", file($hit[1])))."";'), $css);
$inputfile = 'aurel-manga.png';


		// Minify
		if($minify)
		{
			// Remove spaces, tabs, linebreaks,...
			$css = preg_replace("/\\s+/si", ' ', $css);
			$css = str_replace( ' {', '{', $css );
			
			// Remove the spaces after the things that should not have spaces after them.
			$css = preg_replace("/([!{}:;>+\\(\\[,])\\s+/si", "$1", $css);
			// Replace 0(px,em,%) with 0.
			$css = preg_replace("/([\\s:])(0)(px|em|%|in|cm|mm|pc|pt|ex)/is", "$1$2", $css);
		}
		
		$fp = fopen($newfile, "w+");
 		fwrite($fp, $css);
		fclose($fp);

		return $css;
	}
	return false;
}


//  Call UniCSS
css_unify('style.php', 'style.min.css');

?>

<!DOCTYPE html>
<html>
 <head>
  <title>UniCSS</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link type="text/css" href="style.min.css" rel="stylesheet" />
 </head>
 <body>
  <h1>UniCSS Example</h1>
 </body>
</html>