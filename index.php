<!DOCTYPE html>
<html>
	<head>
		<title>Ausstellung</title>
		<script src="jquery.js"></script>
		<style>
			<?php include("style.css"); ?>
		</style>
	</head>
	<body>
		<input style="width: 80%" id="description" value="" placeholder="Beschreibe hier, was chatGPT malen soll" />
		<button id="draw_button" disabled onclick='call_api()'>Malen!</button>

		<div id="history"></div>
		<div id="response"></div>

		<script>
			var currently_awaiting_response = false;

			const delay = (delayInms) => {
				return new Promise(resolve => setTimeout(resolve, delayInms));
			};

			async function print_page_area(areaID){
				var cnt = 0;
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
			}

			function uuidv4() {
				return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
					(c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
				);
			}

			function call_api() {
				currently_awaiting_response = true;
				var uuid = uuidv4();
				var description = $("#description").val();

				description = description.replace(/<\s*script/, "&lt;script");

				try {
					// Clear the input field
					$("#description").val('').attr("disabled", true);
					$("#draw_button").attr("disabled", true);

					// Display loading animation while waiting for the response
					var loadingDiv = '<div><img src="loading.gif" alt="Loading"></div>';

					var responseDiv = $(
						`<div class='full_reply' id="full_${uuid}">` +
							`<img class='no-print print_button' style='display: none' id='print_button_${uuid}' onclick='print_page_area("full_${uuid}")' src='printer.svg' width=50 alt="Drucken" />` + 
							`<div id="response_${uuid}_sent" class="message sent">${description}</div>` +
							`<div id="response_${uuid}_received" class="message received">${response}</div>` +
						`</div>` +
						`<br>`
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
						success: function (response) {
							try {
								// Replace newline characters with <br> in the response
								var r = response.replace(/\\n/g, "\n");
								r = r.replace("</svg>", "</svg><pre style='white-space: pre-wrap'>");
								r = r + "</pre>";

								// Create a new div for each question and answer and append it to the history
								ok++;
								console.log(r);
								$(`#response_${uuid}_received`).html(r);
								$("#description").attr("disabled", false).focus();
								$("#draw_button").attr("disabled", false);

								//$(`#print_button_${uuid}`).show();
								currently_awaiting_response = false;
							} catch (error) {
								console.warn("Fehler beim Hinzufügen zur History: " + error.message);
								$("#description").attr("disabled", false).focus();
								$("#draw_button").attr("disabled", false);
								currently_awaiting_response = false;
							}
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.warn("A: Fehler beim API-Aufruf: " + errorThrown);
							$("#description").attr("disabled", false).focus();
							$("#draw_button").attr("disabled", false);
							currently_awaiting_response = false;
						}
					});

					// Set a timeout to handle cases where the API call takes too long
					setTimeout(function () {
						if(!ok) {
							ajaxRequest.abort(); // Abort the API call on timeout
							$(`#response_${uuid}_received`).html('Fehler: Timeout').css('color', 'red');
						}
						$("#description").attr("disabled", false).focus();
						$("#draw_button").attr("disabled", false);
						currently_awaiting_response = false;
					}, 2*60000); // 2*60 seconds
				} catch (error) {
					console.error("B: Fehler beim API-Aufruf: " + error.message);
					$("#description").attr("disabled", false).focus();
				}
			}

			function keyuprelease (event) {
				// If the user presses the "Enter" key on the keyboard
				var current_text = $("#description").val();

				if(/\w/.test(current_text)) {
					$("#draw_button").attr("disabled", false);

					if (event.key === "Enter") {
						// Cancel the default action, if needed
						event.preventDefault();
						// Trigger the button element with a click
						call_api();
					}
				} else {
					console.warn("Cannot send empty form. Needs to contain at least one letter.");

					$("#draw_button").attr("disabled", true);
				}
			}

			$(document).ready(function() {
				// Execute a function when the user presses a key on the keyboard
				$("#description")[0].addEventListener("keyup", keyuprelease);
				$("#description")[0].addEventListener("keypress", keyuprelease);

				$("#description").focus();
			});
		</script>
	</body>
</html>
