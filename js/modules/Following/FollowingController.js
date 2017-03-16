/**
 * Class: Following
 * Controller for Following.
 */
$.Controller('Following', {pluginName: 'FollowingController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		this.loadTable();
	},

	initEvents: function() {
		this.$el.on('click', 'tr', $.proxy(this.onUserClick, this));
	},

	onUserClick: function() {
		var $dialog = $($('#view-user-details-html').html()).dialog();
		var self    = this;

		// Make sure it destroys instances on close
		$dialog.on('dialogclose', function(event) {
			$dialog.dialog('destroy');
			self.loadTable();
		});

		var windowHeight = $(window).height();
		var windowWidth  = $(window).width();
		var dialogWidth  = 1200;
		var dialogHeight = 800;

		// Center dialog
		$dialog.parent('.ui-dialog').css('height', dialogHeight);
		$dialog.parent('.ui-dialog').css('width',  dialogWidth);
		$dialog.parent('.ui-dialog').css('top',    (windowHeight/2) - (dialogHeight/2));
		$dialog.parent('.ui-dialog').css('left',   (windowWidth/2)  - (dialogWidth/2) );
		$dialog.css('height', 'calc(100% - 33px)');

		// Init the controller to the div
		Utility.Module.launch('UserDetails', $.noop, $dialog);

		$dialog.on('click', '#cancel-button', function() {$dialog.dialog('destroy');});
	},

	loadTable: function() {
		var rows = $('#following-table tr', this.$el);
		for (var ct = 1; ct < rows.length; ct++) {
			$(rows[ct]).remove();
		}
		
		Utility.Ajax.request({
			mode:     'User::getUsers',
			callback: $.proxy(onGetUsersSuccess, this)
		});

		function onGetUsersSuccess(result) {
			if (!result.success)     {return;}

			this.loadData(result.data);
		}
	},

	loadData: function(users) {
		var $table = $('#following-table', this.$el);
		$.each(users, function(index, user) {
			var $row = $('<tr>');

			$row.append($('<td>').append(user.name));
			$row.append($('<td>').append(user.total_bets));
			$row.append($('<td>').append(user.total_units));
			$row.append($('<td>').append(user.percent));
			$row.append($('<td>').append(user.plus_minus));

			$table.append($row);
		});
	}
});

//# sourceURL=FollowingController.js