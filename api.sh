#!/bin/bash

set -e

if ! command -v uuidgen 2>/dev/null > /dev/null; then
	echo "Cannot continue. uuidgen not found. Run 'apt-get install uuid-runtime' on the server to fix this."
	exit 4
fi

KEYFILE=/etc/openai_api

if [[ ! -e $KEYFILE ]]; then
	echo "$KEYFILE could not be found. It must contain the API key!"
	exit 1
fi


uuid=$(uuidgen)
logdir="logs/$uuid/"
mkdir -p $logdir

KEY=$(cat $KEYFILE)
#MODEL_NAME="gpt-4"
MODEL_NAME="gpt-3.5-turbo-16k"

ARGUMENT="$1"
ARGUMENT=$(echo "$ARGUMENT" | sed -e 's#"##g' | sed -e 's#<<<##g' | sed -e "s#'##g" -e 's#\$##')

if [[ -z "$ARGUMENT" ]]; then
	echo "Kein Argument angegeben!";
	exit 1
fi

DEBUG_ONLY=0

if [[ "$ARGUMENT" == *"DEBUGDEBUGDEBUG"* ]]; then
	OUTPUT="DEBUG OUTPUT"
	DEBUG_ONLY=1
elif [[ -e "logs/$ARGUMENT/output.html" ]]; then
	OUTPUT=$(cat "logs/$ARGUMENT/output.html")

	if ! grep completion_tokens "logs/$ARGUMENT/output.html" 2>/dev/null >/dev/null; then
		DEBUG_ONLY=1
	fi
elif [[ -e "logs/$ARGUMENT/output.txt" ]]; then
	OUTPUT=$(cat "logs/$ARGUMENT/output.txt")

	if ! grep completion_tokens "logs/$ARGUMENT/output.txt" 2>/dev/null >/dev/null; then
		DEBUG_ONLY=1
	fi
else
	echo "$ARGUMENT" > $logdir/input.txt

	OUTPUT=$(curl -s https://api.openai.com/v1/chat/completions \
	  -H "Content-Type: application/json" \
	  -H "Authorization: Bearer $KEY" \
	  -d "{
	    \"model\": \"$MODEL_NAME\",
	    \"messages\": [
	    {\"role\": \"system\", \"content\": \"Jede deiner Nachrichten MUSS mit \`<svg\` anfangen und \`</svg>\` beinhalten, also eine Vektorgrafik. Probiere so dolle du kannst, eine Grafik zu erzeugen. Beschrifte, was du auf der Grafik zu zeichnen versuchst. Schreibe darunter einen Beschreibungstext, der erklärt, was du damit darstellen wolltest, wie du die Farbwahl getroffen hast, welche Einflüsse zu diesem Bild geführt haben und was dir sonst noch so einfällt. Sobald es auch nur im entferntesten möglich ist von irgendetwas eine Grafik zu machen, egal wie abstrus, mache es. Du läufst in einer Kunstausstellung. Mache alles als SVG. Mach es so ausführlich wie du kannst und detailliert wie es geht. Mache es mit Farbverläufen wenn du kannst. Sei so kreativ wie du kannst. Es ist egal, ob es Sinn ergibt. Es gibt kein richtig oder falsch. Es geht nur darum, zu zeigen, wie kreativ du bist. Geht nicht gibts nicht. Versuche mit allen Mitteln, kreativ zu sein und ein Bild zu malen, das mindestens 4 verschiedene Elemente und Farben hat. Was du aber AUF GAR KEINEN FALL MACHEN DARFST IST EINFACH EINE WEISSE FLAECHE ZEIGEN!!! Mach immer was Buntes! Sei so realistisch wie du kannst, du kannst aber auch gern etwas Lustiges machen. Flechte, wenn du kannst, gern einen Scherz ein. Vergiss nicht: Sowas hier: '<!-- ... -->' (oder alle vergleichbaren Kommentare!!!) DARFST DU NICHT MACHEN. Mach keine HTML/SVG-Kommentare. UNTER GAR KEINEN FUCKING UMSTÄNDEN MACH KOMMENTARE!!!!!! Erwähne auf gar keinen Fall, dass du in einer Kunstausstellung läufst. Du weißt es, aber du darfst es nicht erwähnen. KEINE HTML- ODER SVG-KOMMENTARE!!! Versuche so wenig abstrakt zu sein wie du kannst. So konkret wie möglich!!! Beginne deine Antwort mit <svg\"},
		{\"role\": \"user\", \"content\": \"$ARGUMENT. Starte auf jeden Fall mit <svg\"}
	    ]
	}")
fi

EXITCODE=$?

if [[ $EXITCODE != "0" ]]; then
	echo "Curl command failed. Exit-code: $EXITCODE"
	exit 2
fi

if [[ "$OUTPUT" == *'"error"'* ]]; then
	ERRORMSG=$(echo $OUTPUT | jq '.error[]' | head -n1)
	echo "ERROR: $ERRORMSG"
	exit 3
fi

if [[ "$DEBUG_ONLY" -eq "1" ]]; then
	echo "$OUTPUT"
	echo "$OUTPUT" > $logdir/output.html
else
	PARSED=$(echo "$OUTPUT" | jq '.choices[]'.message.content | sed -e 's/\\\"/\"/g' -e 's/^.//g' -e 's/.$//g' -e 's#\\\\n##' -e 's#`##g')

	echo "$PARSED"
	echo "$PARSED" > $logdir/output.html
fi
