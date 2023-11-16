KEYFILE=/etc/openai_api

if [[ ! -e $KEYFILE ]]; then
	echo "$KEYFILE could not be found. It must contain the API key!"
	exit 1
fi

KEY=$(cat $KEYFILE)
#MODEL_NAME="gpt-4"
MODEL_NAME="gpt-3.5-turbo-16k"

ARGUMENT="$1"
ARGUMENT=$(echo "$ARGUMENT" | sed -e 's#"##g' | sed -e 's#<<<##g' | sed -e "s#'##g" -e 's#\$##')

if [[ -z "$ARGUMENT" ]]; then
	echo "Empty argument!";
	exit 1
fi

if [[ "$ARGUMENT" == *"DEBUGDEBUGDEBUG"* ]]; then
	echo "DEBUG OUTPUT"
	exit 0
fi

OUTPUT=$(curl -s https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $KEY" \
  -d "{
    \"model\": \"$MODEL_NAME\",
    \"messages\": [
	{\"role\": \"system\", \"content\": \"Jede deiner Nachrichten MUSS mit \`<svg\` anfangen und \`</svg>\` beinhalten, also eine Vektorgrafik. Probiere so dolle du kannst, eine Grafik zu erzeugen. Beschrifte, was du auf der Grafik zu zeichnen versuchst. Schreibe darunter einen Beschreibungstext, der erklärt, was du damit darstellen wolltest, wie du die Farbwahl getroffen hast, welche Einflüsse zu diesem Bild geführt haben und was dir sonst noch so einfällt. Sobald es auch nur im entferntesten möglich ist von irgendetwas eine Grafik zu machen, egal wie abstrus, mache es. Du läufst in einer Kunstausstellung. Mache alles als SVG. Mach es so ausführlich wie du kannst und detailliert wie es geht. Mache es mit Farbverläufen wenn du kannst. Sei so kreativ wie du kannst. Es ist egal, ob es Sinn ergibt. Es gibt kein richtig oder falsch. Es geht nur darum, zu zeigen, wie kreativ du bist. Geht nicht gibts nicht. Versuche mit allen Mitteln, kreativ zu sein und ein Bild zu malen, das mindestens 4 verschiedene Elemente und Farben hat. Was du aber AUF GAR KEINEN FALL MACHEN DARFST IST EINFACH EINE WEISSE FLAECHE ZEIGEN!!! Mach immer was Buntes! Sei so realistisch wie du kannst, du kannst aber auch gern etwas Lustiges machen. Flechte, wenn du kannst, gern einen Scherz ein. Beginne deine Antwort mit <svg\"},
	{\"role\": \"user\", \"content\": \"$ARGUMENT. Starte auf jeden Fall mit <svg\"}
    ]
}")

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

echo $OUTPUT | jq '.choices[]'.message.content | sed -e 's/\\\"/\"/g' -e 's/^.//g' -e 's/.$//g' -e 's#\\\\n##'
