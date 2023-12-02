<!DOCTYPE html>
<html>
	<head>
		<title>Ausstellung</title>
		<script src="jquery.js"></script>
		<link rel="stylesheet" href="style.css">
		<link rel="stylesheet" href="animate.css">
	</head>
	<body>
<?php
// Funktion zum Lesen des Inhalts einer Datei
function readContent($filePath) {
	try {
		$content = file_get_contents($filePath);
		return $content !== false ? $content : '';
	} catch (Exception $e) {
		return '';
	}
}

// Verzeichnis mit Unterordnern
$directory = 'logs';

// Array für die Unterordner
$subfolders = [];

// Ordner einlesen
if ($handle = opendir($directory)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != ".." && is_dir($directory . '/' . $entry)) {
			$subfolders[] = $entry;
		}
	}
	closedir($handle);
}

// Sortiere die Unterordner nach dem Änderungsdatum
usort($subfolders, function($a, $b) use ($directory) {
	$fileA = $directory . '/' . $a . '/output.html';
	$fileB = $directory . '/' . $b . '/output.html';
	return filemtime($fileB) - filemtime($fileA);
});

// HTML-Ausgabe für jeden Unterordner
foreach ($subfolders as $subfolder) {
	$inputPath = $directory . '/' . $subfolder . '/input.txt';
	$outputPath = $directory . '/' . $subfolder . '/output.html';

	$inputContent = readContent($inputPath);
	$outputContent = readContent($outputPath);

	if(!preg_match("/DEBUG/", $outputContent) && !preg_match("/DEBUG/", $inputContent)) {
		echo '<div class="input">' . $inputContent . '</div>';
		echo '<div class="output">' . $outputContent . '</div>';
	}
}

?>
</body>
</html>
