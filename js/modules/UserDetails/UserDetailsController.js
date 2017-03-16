/**
 * Class: UserDetails
 * Controller for UserDetails.
 */
$.Controller('UserDetails', {pluginName: 'UserDetailsController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		$("a:contains('Dashboard')", this.$el).trigger('click');
	},

	initEvents: function() {
		this.$el.on('click', '.nav a', $.proxy(this.onNavBarItemClick,    this));
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
		if (!$currentModule) {
			Utility.Module.launch({path: search, fade: true}, $.noop, '#user-details-main-container');
		}
		else {
			$currentModule.fadeOut('fast', function() {
				// Make sure all of them are hidden at this point... if someone is fast enough they could have activated two modules by clicking quickly
				$("#user-details-main-container", this.$el).children('div').hide();

				// Activate the controller if it needs activated
				if (!$('.'+search).length) {
					Utility.Module.launch({path: search, fade: true}, $.noop, '#user-details-main-container');
				}
				else {
					$('.'+search).fadeIn('fast');
				}
			});
		}
	},

	getCurrentModule: function() {
		var children = $('#user-details-main-container').children('div');
		var active   = null;
		$.each(children, function(index, child) {
			var $child = $(child);
			if ($child.is(':visible')) {active = $child;return false;}
		});
		return active;
	}
});

//# sourceURL=UserDetailsController.js