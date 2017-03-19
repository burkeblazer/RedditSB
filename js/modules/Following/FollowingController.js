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
		this.$el.on('click', '.following-star',     $.proxy(this.unfollowUser,  this));
		this.$el.on('click', '.not-following-star', $.proxy(this.followUser,    this));
		this.$el.on('click', '#add-reddit-user',    $.proxy(this.addRedditUser, this));
	},

	addRedditUser: function() {
		var followName = $('#add-reddit-user-text', this.$el).val();
		if (!followName) {return;}

		Utility.Ajax.request({
			mode:     'User::followUser',
			name:     followName,
			callback: $.proxy(onFollowUserSuccess, this)
		});

		function onFollowUserSuccess(result) {
			if (!result.success) {return;}

			this.loadTable();
		}
	},

	unfollowUser: function(evt) {
		var $target      = $(evt.target);
		var unfollowName = $($(evt.target).closest('tr').find('td')[0]).text();

		Utility.Ajax.request({
			mode:     'User::unfollowUser',
			name:     unfollowName,
			callback: $.proxy(onUnfollowUserSuccess, this)
		});

		function onUnfollowUserSuccess(result) {
			if (!result.success) {return;}

			this.loadTable();
		}
	},

	followUser: function(evt) {
		var $target    = $(evt.target);
		var followName = $($(evt.target).closest('tr').find('td')[0]).text();

		Utility.Ajax.request({
			mode:     'User::followUser',
			name:     followName,
			callback: $.proxy(onFollowUserSuccess, this)
		});

		function onFollowUserSuccess(result) {
			if (!result.success) {return;}

			this.loadTable();
		}
	},

	loadTable: function() {
		var rows = $('#following-table tr', this.$el);
		for (var ct = 1; ct < rows.length; ct++) {
			$(rows[ct]).remove();
		}
		
		Utility.Ajax.request({
			mode:     'User::getFollowing',
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
			$row.append($('<td>').append($('<div>').addClass((user.user_following_id) ? 'following-star' : 'not-following-star')));

			$table.append($row);
		});
	}
});

//# sourceURL=FollowingController.js