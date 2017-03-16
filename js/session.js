window.session = window.session || {
	warningInactivityLength:    13,
	expirationInactivityLength: 2,
	warningTimerEnabled:        true
};

resetWarningTimer();
$(document).mousemove(resetWarningTimer);

function resetWarningTimer() {
	window.clearTimeout(window.session.warningTimer);

	if (window.session.warningTimerEnabled && window.UserData)
	{
		window.session.warningTimer = setTimeout(window.initiateSessionWarning, 1000 * 60 * window.session.warningInactivityLength); // 13 minutes
	}
}

function initiateSessionWarning() {
	if (!window.session.warningTimerEnabled) {return;}

	// Disable and reset the warning timer
	window.session.warningTimerEnabled = false;
	resetWarningTimer();

	// Create the popup
	window.session.timeUntilExpiration = moment.duration(window.session.expirationInactivityLength, 'minutes');
	var countdownText                  = moment(window.session.timeUntilExpiration).format('mm:ss');
	window.session.$modal              = Utility.Modal.confirm('Session Expiration', 'Your session will expire in ' + countdownText + '. Click continue to extend your session.', $.proxy(cancelSessionExpiration, this));

	$('.modal-footer', window.session.$modal).empty().append('<button class="btn modal-confirm">Continue</button>');
	window.session.$modal.css('zIndex', 2000).modal({backdrop: false});

	$('.modal-confirm', window.session.$modal).click($.proxy(cancelSessionExpiration, this));
	$('.close',         window.session.$modal).click($.proxy(cancelSessionExpiration, this));

	// Set up the warning update
	window.session.warningUpdateTimer  = setInterval(updateSessionWarning, 1000);
}

function updateSessionWarning() {
	window.session.timeUntilExpiration = moment.duration(window.session.timeUntilExpiration - moment.duration(1, 'seconds'));

	if (window.session.timeUntilExpiration.asMilliseconds() >= 0)
	{
		// Show how many hours, minutes and seconds are left
		var countdownText = moment(window.session.timeUntilExpiration).format('mm:ss');
		$('.modal-body', window.session.$modal).text('Your session will expire in ' + countdownText + '. Click continue to extend your session.');
	}
	else
	{
		destroySession();
	}
}

function cancelSessionExpiration(eventObject) {
	// Halt the countdown
	clearInterval(window.session.warningUpdateTimer);

	// Destroy the popup
	window.session.$modal.modal('hide');

	// Re-enable and reset the warning timer
	window.session.warningTimerEnabled = true;
	resetWarningTimer();
}

function destroySession() {
	cancelSessionExpiration();

	// Log them out and take them back to the log in screen
	$.ajax({
		data: {
			mode: 'User::logout'
		},
		context: this,
		success: function(results) {
			window.location.reload();
		},
		error: function(results) {
			window.location.reload();
		}
	});
}