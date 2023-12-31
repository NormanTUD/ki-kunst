var print_queue = [];

function error(...args) {
	console.error(...args);
}

function warn(...args) {
	console.warn(...args);
}

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
			log("Currently waiting for response. Printing when response is there...");
		}

		cnt++;
		await delay(200);
	}

	if(cnt) {
		log("Finished waiting for response. Printing now.");
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
					//log(r);
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
		}, 4*60000); // 4*60 seconds
	} catch (error) {
		console.error("Fehler 2 beim API-Aufruf: " + error.message);
		$("#description").attr("disabled", false).focus();
	}
}

$(document).ready(function() {
	$("#description").focus();
});

function keytype (e) {
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

	} else if(e.key == "Backspace") {
		if(!$("#description").is(":focus")) {
			let descriptionField = document.querySelector("#description");
			if (descriptionField.value.length > 0) {
				// Letztes Zeichen entfernen
				descriptionField.value = descriptionField.value.slice(0, -1);
			}

			$(descriptionField).focus();
		}
	} else {
		if(!$("#description").is(":focus")) {
			if(/^\w$/.test(e.key)) {
				$("#description").val($("#description").val() + e.key).focus();
			} else {
				$("#description").focus();
			}
		}
	}
}

document.body.addEventListener('keyup', keytype);

var time = new Date().getTime();
$(document.body).bind("keypress keyup", function(e) {
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
