window.CONSTANTS                  = {};
window.CONSTANTS.reddit_client_id = '9TStV54dkfQcBA';

$.ajaxSetup({
	url:      'c.php',
	dataType: 'json',
	cache:    true,
	type:     'POST',
	complete: function(xhr, textStatus) {
		if (this.url && this.url.indexOf('c') == -1) {return;}

		var response = $.parseJSON(xhr.responseText);

		if (!response) {return;}

		if ( !response.success &&
			 response.logout   &&
			 response.error    &&
			 response.error === 'Session expired')
		{
			Utility.Notify.error('Session Timeout', 'You will now be redirected to the login page.');
			setTimeout(function(){window.location.reload();},5000);
		}
	}
});