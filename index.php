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
		<input style="width: 80%" id="description" value="" placeholder="Beschreibe hier, was chatGPT malen soll" /> <button id="draw_button" disabled onclick='call_api()'>Malen!</button>
		<div id="history"></div>
		<div id="response"></div>
		<script>
			var response_counter = 0;
			var global_r;
			function call_api() {
				response_counter++;
				var description = $("#description").val();
				try {
					// Clear the input field
					$("#description").val('').attr("disabled", true);
					$("#draw_button").attr("disabled", true);

					// Display loading animation while waiting for the response
					var loadingDiv = '<div><img src="loading.gif" alt="Loading"></div>';

					var responseDiv = $(
						'<div id="response_' + response_counter + '_sent" class="message sent">' + description + '</div>' +
						'<div id="response_' + response_counter + '_received" class="message received">' + response + '</div>'
					);

					var ok = 0;

					$("#history").prepend(responseDiv);

					$("#response_" + response_counter + "_received").html(loadingDiv);

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
								global_r = r;
								$("#response_" + response_counter + "_received").html(r);
								$("#description").attr("disabled", false).focus();
							} catch (error) {
								console.warn("Fehler beim Hinzufügen zur History: " + error.message);
								$("#description").attr("disabled", false).focus();
								$("#draw_button").attr("disabled", false);
							}
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.warn("A: Fehler beim API-Aufruf: " + errorThrown);
							$("#description").attr("disabled", false).focus();
							$("#draw_button").attr("disabled", false);
						}
					});

					// Set a timeout to handle cases where the API call takes too long
					setTimeout(function () {
						if(!ok) {
							ajaxRequest.abort(); // Abort the API call on timeout
							$("#response_" + response_counter + "_received").html('Fehler: Timeout').css('color', 'red');
						}
						$("#description").attr("disabled", false).focus();
						$("#draw_button").attr("disabled", false);
					}, 2*60000); // 2*60 seconds
				} catch (error) {
					console.error("B: Fehler beim API-Aufruf: " + error.message);
					$("#description").attr("disabled", false).focus();
				}
			}

			$(document).ready(function() {
				// Execute a function when the user presses a key on the keyboard
				$("#description")[0].addEventListener("keyup", function(event) {
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
				});

				$("#description").focus();
			});
		</script>
	</body>
</html>
