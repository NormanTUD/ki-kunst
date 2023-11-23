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
		<script src="html2canvas.min.js"></script>
		<style>
			<?php include("style.css"); ?>
			<?php include("animate.css"); ?>
		</style>
	</head>
	<body>
		<input style="width: 80%" id="description" value="" placeholder="Beschreibe hier, was chatGPT malen soll" />

		<div id="examples">
			<br>
			Beispiele:
			<ul>
				<li>Eine süße Katze</li>
				<li>Die Sydney-Opera</li>
				<li>Die Erde</li>
			</ul>
			Auch bei gleicher Eingabe ist die Ausgabe nie die Gleiche. Daher: probiere es ruhig mehrfach!<br>
			Fang einfach an das zu tippen, was du gemalt haben möchtest, und drücke Enter, wenn du fertig bist.<br>
			Die Berechnung der Antwort dauert ca. 1-3 Minuten.<br>
			Klicke auf das Drucker-Symbol und dann nochmal auf 'Print', um die Antwort auszudrucken.
		</div>

		<div id="history"></div>
		<div id="response"></div>

		<script src="main.js"></script>
	</body>
</html>
