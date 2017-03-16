/**
 * Class: Header
 * Controller for Header.
 */
$.Controller('Header', {pluginName: 'HeaderController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		// Check to see if we have a local stored auth key
		if (window.localStorage.rsb_auth_key) {
			this.rs = window.localStorage.rsb_auth_key;
			this.startAuthPoll();
		}
	},

	initEvents: function() {
		this.$el.on('click', '#signinbutton',  $.proxy(this.onSignInButtonClick,  this));
		this.$el.on('click', '#signoutbutton', $.proxy(this.onSignOutButtonClick, this));

		this.$el.on('click', '.nav a',         $.proxy(this.onNavBarItemClick,    this));
	},

	onNavBarItemClick: function(evt) {
		// Clear any other active items
		var $items = $('.nav li', this.$el);
		$.each($items, function(index, item) {
			var $currentItem = $(item);
			$currentItem.removeClass('active');
		});

		// Activate the current tab
		var $item = $(evt.target);
		$item.parent().addClass('active');

		// Strip any spaces in the tab name so we can get the controller and view
		var tabName = $item.text();
		var search  = tabName.replace(/\s+/g, '');

		// Fade out any current module
		var $currentModule = this.getCurrentModule();
		$currentModule.fadeOut('fast', function() {
			// Make sure all of them are hidden at this point... if someone is fast enough they could have activated two modules by clicking quickly
			$('#main-container').children('div').hide();

			// Activate the controller if it needs activated
			if (!$('.'+search).length) {
				Utility.Module.launch({path: search, fade: true}, $.noop, '#main-container');
			}
			else {
				$('.'+search).fadeIn('fast');
			}
		});
	},

	getCurrentModule: function() {
		var children = $('#main-container').children('div');
		var active   = null;
		$.each(children, function(index, child) {
			var $child = $(child);
			if ($child.is(':visible')) {active = $child;return false;}
		});
		return active;
	},

	onSignOutButtonClick: function() {
		Utility.Ajax.request({
			mode:     'User::logOut',
			callback: $.proxy(onLogOutSuccess, this)
		});

		function onLogOutSuccess(result) {
			if (!result.success) {return;}

			window.UserData                  = null;
			window.localStorage.rsb_auth_key = null;
			location.reload();
		}
	},

	onSignInButtonClick: function() {
		this.rs = '_' + Math.random().toString(36).substr(2, 9);
		var red = window.location.href+'Reddit.php';
		var url = 'https://www.reddit.com/api/v1/authorize?client_id='+window.CONSTANTS.reddit_client_id+'&response_type=code&state='+this.rs+'&redirect_uri='+red+'&scope=identity';
		window.open(url, '_blank');

		// Start polling to see if allowed
		this.interval = setInterval($.proxy(this.startAuthPoll, this), 1000);
		this.startAuthPoll();
	},

	startAuthPoll: function() {
		Utility.Ajax.request({
			mode:     'User::logIn',
			auth_key: this.rs,
			callback: $.proxy(checkAuth, this)
		});

		function checkAuth(result) {
			if (!result.success) {return;}

			// Set auth key to ls and then clear any intervals that may exist or don't exist
			window.localStorage.rsb_auth_key = this.rs;
			this.rs                          = null;
			clearInterval(this.interval);
			this.interval                    = null;

			// Continue the process after logging in
			this.continueLogIn(result.data);
		}
	},

	continueLogIn: function(userData) {		
		window.UserData = userData;
		$('#signinbutton').hide();
		$('#welcome-message').show().css('display', 'inline-block');
		$('#welcome-message').text('Welcome back '+userData.name+'!');
		$('#signoutbutton').show();
		$('#header-navbar').show();
		$("a:contains('Dashboard')", this.$el).trigger('click');
	}
});

//# sourceURL=HeaderController.js