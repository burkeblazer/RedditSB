/**
 * Class: Footer
 * Controller for Footer.
 */
$.Controller('Footer', {pluginName: 'FooterController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		$('#current-year').text(new Date().getFullYear());
	},

	initEvents: function() {

	}
});

//# sourceURL=FooterController.js