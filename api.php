<?php
// Erhalten Sie die übergebene Beschreibung von JavaScript
$description = $_POST['description'];

$description = preg_replace("/[^a-zA-ZäÄöÖüÜß]/", " ", $description);

try {
	// Übergeben Sie die Beschreibung an die Shell-Ausführung und speichern Sie die Ausgabe in $output
	$output = shell_exec("bash api.sh " . escapeshellarg($description));

	if ($output !== null) {
		// Senden Sie die Ausgabe zurück an JavaScript
		echo $output;
	} else {
		// Keine Ausgabe erhalten
		echo "Keine Ausgabe von api.sh erhalten.";
	}
} catch (Exception $e) {
	// Fehler beim Aufrufen der api.sh oder anderen Ausnahmen
	echo "Fehler aufgetreten: " . $e->getMessage();
}
?>
