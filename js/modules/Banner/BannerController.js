/**
 * Class: Banner
 * Controller for the Banner Container.
 */
$.Controller('Banner', {pluginName: 'BannerController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		// TODO: Could maybe display all user's success here or the site's success etc
	},

	initEvents: function() {

	}
});

//# sourceURL=MainController.js