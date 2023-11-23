<?php
	/*
       	TODO:

	QR-Code-Download
	Nur 10 Antworten anzeigen
	*/
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Ausstellung</title>
		<script src="jquery.js"></script>
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
			Klicke auf das Drucker-Symbol, um die Antwort auszudrucken.
		</div>

		<div id="history"></div>
		<div id="response"></div>

		<script>
			var print_queue = [];

			function log(...args) {
				console.log(...args);
			}

			var currently_awaiting_response = false;
			set_currently_awaiting_response(false);

			const delay = (delayInms) => {
				return new Promise(resolve => setTimeout(resolve, delayInms));
			};

			async function print_page_area(areaID){
				var cnt = 0;
				if(print_queue.includes(areaID)) {
					log("Already in print queue. Not printing again.");
					return;
				}

				print_queue.push(areaID);

				while (currently_awaiting_response) {
					if(cnt == 0) {
						console.log("Currently waiting for response. Printing when response is there...");
					}

					cnt++;
					await delay(200);
				}

				if(cnt) {
					console.log("Finished waiting for response. Printing now.");
				}

				var printContent = document.getElementById(areaID).innerHTML;
				var originalContent = document.body.innerHTML;
				document.body.innerHTML = printContent;
				window.print();
				document.body.innerHTML = originalContent;

				var index = print_queue.indexOf(areaID);
				if (index !== -1) {
					print_queue.splice(index, 1);
				}
			}

			function uuidv4() {
				return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
					(c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
				);
			}

			function rand_between (a, b) {
				var min = Math.min(a, b);
				var max = Math.max(a, b);

				if(min == max) {
					console.error(`rand_between: a and b are equal!`);
					return 5;
				} else {
					var r = Math.floor(Math.random() * max) + min;

					return r;
				}
			}

			function set_currently_awaiting_response (val) {
				val = !!val;

				if(val != currently_awaiting_response) {
					console.log("Setting currently_awaiting_response to " + val);
					console.trace();

					currently_awaiting_response = val;
				}
			}

			function call_api() {
				set_currently_awaiting_response(true);
				var uuid = uuidv4();
				var description = $("#description").val();

				description = description.replace(/<\s*script/, "&lt;script");

				if(/^\s*$/.test(description)) {
					return;
				}

				try {
					// Clear the input field
					$("#description").val('').attr("disabled", true);

					// Display loading animation while waiting for the response
					var loadingDiv = `<div><img width='64' src="spinner.svg" alt="Loading"></div>`;

					var responseDiv = $(
						`<div class='full_reply' id="full_${uuid}">` +
							`<img class='no-print print_button' style='display: none' id='print_button_${uuid}' onclick='print_page_area("full_${uuid}")' src='printer.svg' width=50 alt="Drucken" />` + 
							`<div id="response_${uuid}_sent" class="message sent">${description}</div>` +
							`<div id="response_${uuid}_received" class="message received">${response}</div>` +
							`<br>` + 
						`</div>`
					);

					var ok = 0;

					$("#history").prepend(responseDiv);

					$(`#response_${uuid}_received`).html(loadingDiv);

					var ajaxRequest = $.ajax({
						type: "POST",
						url: "api.php",
						data: {
							description: description
						},
						success: async function (response) {
							try {
								// Replace newline characters with <br> in the response
								var r = response.replace(/\\n/g, "\n");
								r = r.replace("</svg>", "</svg><pre style='white-space: break-spaces'>");
								r = r + "</pre>";

								// Create a new div for each question and answer and append it to the history
								//console.log(r);
								var splitted = r.split("");

								$(`#response_${uuid}_received`).html("");

								for (var j = 0; j < splitted.length; j++) {
									$(`#response_${uuid}_received`).append(splitted[j]);
									if(j % rand_between(20, 40) == 0) {
										await delay(rand_between(30, 70));
									}
								}

								$(`#response_${uuid}_received`).html(r);
								$("#description").attr("disabled", false).focus();


								$(`#print_button_${uuid}`).show();
								set_currently_awaiting_response(false);
								ok++;
							} catch (error) {
								console.warn("Fehler beim Hinzufügen zur History: " + error.message);
								$("#description").attr("disabled", false).focus();
								set_currently_awaiting_response(false);
							}

							remove_old_answers();
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.warn("Fehler 1 beim API-Aufruf: " + errorThrown);
							$("#description").attr("disabled", false).focus();
							set_currently_awaiting_response(false);

							remove_old_answers();
						}
					});

					// Set a timeout to handle cases where the API call takes too long
					setTimeout(function () {
						if(!ok) {
							ajaxRequest.abort(); // Abort the API call on timeout
							$(`#response_${uuid}_received`).html('Fehler: Timeout. Bitte erneut probieren.').css('color', 'red');
						}
						$("#description").attr("disabled", false).focus();
						set_currently_awaiting_response(false);
					}, 3*60000); // 3*60 seconds
				} catch (error) {
					console.error("Fehler 2 beim API-Aufruf: " + error.message);
					$("#description").attr("disabled", false).focus();
				}
			}

			$(document).ready(function() {
				$("#description").focus();
			});

			function type (e) {
				if($("#description").prop('disabled')) {
					console.warn(`Description field is currently disabled`);
					return;
				}

				if(e.key == "Enter") {
					var current_text = $("#description").val();

					if(/\w.*\w/.test(current_text)) {
						call_api();
						$("#description").val();
					}
				} else {
					if(!$("#description").is(":focus")) {
						$("#description").val($("#description").val() + e.key).focus();
					}
				}
			}

			document.body.addEventListener('keypress', type);

			var time = new Date().getTime();
			$(document.body).bind("keypress", function(e) {
				time = new Date().getTime();
			});

			function refresh() {
				if(new Date().getTime() - time >= (5 * 60000)) {
					window.location.reload(true);
				} else {
					setTimeout(refresh, 10000);
				}
			}

			setTimeout(refresh, 10000);

			function remove_old_answers () {
				var allFullReplies = $(".full_reply");
				var first10FullReplies = allFullReplies.slice(0, 10);
				allFullReplies.not(first10FullReplies).remove();
			}
		</script>
	</body>
</html>
