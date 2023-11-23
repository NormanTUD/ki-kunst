<?php
	/*
       	TODO:

	QR-Code-Download
	*/
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Ausstellung</title>
		<script src="jquery.js"></script>
		<link rel="stylesheet" href="style.css">
		<link rel="stylesheet" href="animate.css">
	</head>
	<body>
		<input style="width: 80%" id="description" value="" placeholder="Beschreibe hier, was chatGPT malen soll. Tippe einfach los" />

		<div id="examples">
			<br>
			Beispiele:
			<ul>
				<li>Eine süße Katze</li>
				<li>Die Sydney-Opera</li>
				<li>Ein animiertes Logo, das für für universellen Frieden steht</li>
			</ul>
			Auch bei gleicher Eingabe ist die Ausgabe nie die Gleiche. Daher: probiere es ruhig mehrfach!<br>
			Fang einfach an das zu tippen, was du gemalt haben möchtest, und drücke Enter, wenn du fertig bist.<br>
			Die Berechnung der Antwort dauert ca. 1-4 Minuten.<br>
			Klicke auf das Drucker-Symbol und dann nochmal auf 'Print', um die Antwort auszudrucken.
		</div>

		<div id="history"></div>
		<div id="response"></div>

		<script src="main.js"></script>
	</body>
</html>
