<?php

// UniCSS with Minifying
function css_unify( $file, $newfile, $minify=TRUE, $make=NULL )
{
	// $file-Datumstempel neuer als $newfile-Datumstempel
	// ODER $newfile-Datei existiert nicht
	// ODER $make==TRUE, d.h. es wird eine neue Generierung erzwungen
	if( ( filemtime($file) > filemtime($newfile) || !file_exists($newfile) ) || $make==TRUE )
	{
		$unicss = @file_get_contents($file);
		
		// Erstellen des normalen CSS von UNICSS
		$css = trim($unicss);
		$css = preg_replace("/\/\*(.*)?\*\//Usi", '', $css); // Kommentare entfernen: /* */
		
		// Variablen einlesen: $var:value;
		global $variables; // Variablen-Array
		$css = trim($css);
		$css = preg_replace_callback('/(\$[a-zA-Z0-9_-]+):+(.+);+/Usi', create_function('$treffer', 'global $variables;$variables[] = array(trim($treffer[1]), trim($treffer[2]));return "";'), $css, -1, $count); // $result = 

		// Variablen ersetzen
		$css = trim($css);
		for($i=0; $i<$count; $i++) {
			$css = preg_replace("/\\".$variables[$i][0].'/', $variables[$i][1], $css);
		}
		
		// Funktionen: @opacity:0.5; wird zu opacity:0.5;-moz-opacity:0.5;...
		$css = preg_replace_callback('/@opacity:(.+);/Usi', create_function('$treffer', 'return "-moz-opacity:$treffer[1];-khtml-opacity:$treffer[1];-ms-filter:\"alpha(opacity=".($treffer[1]*100).")\";filter:alpha(opacity=".($treffer[1]*100).");opacity:$treffer[1];";'), $css, -1, $count);
		// Funktionen: @box-shadow
		$css = preg_replace_callback('/@box-shadow:(.+);/Usi', create_function('$treffer', 'return "-moz-box-shadow:$treffer[1];-webkit-box-shadow:$treffer[1];box-shadow:$treffer[1];";'), $css, -1, $count);
		// Funktionen: @border-radius
		$css = preg_replace_callback('/@border-radius:(.+);/Usi', create_function('$treffer', 'return "-moz-border-radius:$treffer[1];-webkit-border-radius:$treffer[1];border-radius:$treffer[1];";'), $css, -1, $count);
		// Funktionen: @transition: all 0.3s ease-out;
		$css = preg_replace_callback('/@transition:(.+);/Usi', create_function('$treffer', 'return "-webkit-transition:$treffer[1];-moz-transition:$treffer[1];-o-transition:$treffer[1];";'), $css, -1, $count);
		// Funktionen: @transform: scale(1.05);
		$css = preg_replace_callback('/@transform:(.+);/Usi', create_function('$treffer', 'return "-webkit-transform:$treffer[1];-moz-transform:$treffer[1];transform:$treffer[1];";'), $css, -1, $count);
		// Funktionen: @box-sizing:content-box;
		$css = preg_replace_callback('/@box-sizing:(.+);/Usi', create_function('$treffer', 'return "-webkit-box-sizing:$treffer[1];-moz-box-sizing:$treffer[1];-ms-box-sizing:$treffer[1];box-sizing:$treffer[1];";'), $css, -1, $count);
		
		// Minimierung
		if($minify)
		{
			// Entferne Leerzeichen, Tabs, Umbrüche
			$css = preg_replace("/\\s+/si", ' ', $css);
			$css = str_replace( ' {', '{', $css );
			
			// Remove the spaces after the things that should not have spaces after them.
			$css = preg_replace("/([!{}:;>+\\(\\[,])\\s+/si", "$1", $css);
			// Replace 0(px,em,%) with 0.
			$css = preg_replace("/([\\s:])(0)(px|em|%|in|cm|mm|pc|pt|ex)/is", "$1$2", $css);
			//$css = str_replace(' {', '{', $css);
		}
		
		$fp = fopen($newfile, "w+");
 		fwrite($fp, $css);
		fclose($fp);

		return $css;
	}
	return NULL;
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