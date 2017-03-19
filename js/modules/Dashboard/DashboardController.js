/**
 * Class: Dashboard
 * Controller for Dashboard.
 */
$.Controller('Dashboard', {pluginName: 'DashboardController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		var self = this;
		Utility.DataSource.getSports(function(data) {
			self.sports = data;
			self.loadDashboard();
		});
	},

	initEvents: function() {
		this.$el.on('click', '.newsfeed-item-footer-like-button',     $.proxy(this.onLikeButtonClick,                 this));
		this.$el.on('click', '.newsfeed-item-footer-comments-button', $.proxy(this.onCommentsButtonClick,             this));
		this.$el.on('click', '.newsfeed-item-footer-refresh-button',  $.proxy(this.onRefreshCommentsLikesButtonClick, this));
		this.$el.on('click', '.newsfeed-item-comments-button',        $.proxy(this.onPostCommentButtonClick,          this));
		this.$el.on('click', '.newsfeed-comment-delete',              $.proxy(this.onDeleteCommentButtonClick,        this));
	},

	onDeleteCommentButtonClick: function(evt) {
		var $button          = $(evt.target);
		var betSlipCommentID = $button.closest('.newsfeed-comment').data('commentData').bet_slip_comment_id;
		var betSlipID        = $button.closest('.newsfeed-item').data('newsfeedData').bet_slip_id;
		var self             = this;
		Utility.Ajax.request({
			mode:                'Dashboard::removeComment',
			bet_slip_comment_id: betSlipCommentID,
			callback:            function() {self.refreshCommentsLikes(betSlipID);}
		});		
	},

	onPostCommentButtonClick: function(evt) {
		var $button   = $(evt.target);
		var text      = $button.closest('.input-group').find('.newsfeed-item-comments-input').val();
		$button.closest('.input-group').find('.newsfeed-item-comments-input').val("");
		var betSlipID = $button.closest('.newsfeed-item').data('newsfeedData').bet_slip_id;
		var self      = this;
		Utility.Ajax.request({
			mode:        'Dashboard::postComment',
			bet_slip_id: betSlipID,
			comment:     text,
			callback:    function() {self.refreshCommentsLikes(betSlipID);}
		});
	},

	onRefreshCommentsLikesButtonClick: function(evt) {
		var $button   = $(evt.target);
		var betSlipID = $button.closest('.newsfeed-item').data('newsfeedData').bet_slip_id;
		this.refreshCommentsLikes(betSlipID);
	},

	onLikeButtonClick: function(evt) {
		var $button   = $(evt.target);
		var $badge    = $button.find('.badge');
		var likes     = $badge.text()*1;
		var betSlipID = $button.closest('.newsfeed-item').data('newsfeedData').bet_slip_id;
		if ($button.hasClass('btn-default')) {
			$button.addClass('btn-primary');
			$button.removeClass('btn-default');
			$badge.text(likes+1);

			this.saveLike(betSlipID);
		}
		else {
			$button.addClass('btn-default');
			$button.removeClass('btn-primary');
			$badge.text(likes-1);

			this.unSaveLike(betSlipID);
		}
	},

	onCommentsButtonClick: function(evt) {
		var $button   = $(evt.target);
		if ($button.hasClass('btn-default')) {
			$button.addClass('btn-primary');
			$button.removeClass('btn-default');

			$button.closest('.newsfeed-item').find('.newsfeed-item-comments-container').show();
		}
		else {
			$button.addClass('btn-default');
			$button.removeClass('btn-primary');

			$button.closest('.newsfeed-item').find('.newsfeed-item-comments-container').hide();
		}
	},

	saveLike: function(betSlipID) {
		var self = this;
		Utility.Ajax.request({
			mode:        'Dashboard::addLike',
			bet_slip_id: betSlipID,
			callback:    function() {self.refreshCommentsLikes(betSlipID);}
		});
	},

	unSaveLike: function(betSlipID) {
		var self = this;
		Utility.Ajax.request({
			mode:        'Dashboard::removeLike',
			bet_slip_id: betSlipID,
			callback:    function() {self.refreshCommentsLikes(betSlipID);}
		});
	},

	refreshCommentsLikes: function(betSlipID) {
		Utility.Ajax.request({
			mode:        'Dashboard::getCommentsLikes',
			bet_slip_id: betSlipID,
			callback:    $.proxy(onRefreshCommentsLikesSuccess, this)
		});

		function onRefreshCommentsLikesSuccess(result) {
			var likes    = result.data.likes;
			var comments = result.data.comments;

			this.updateLikes(likes,       betSlipID);
			this.updateComments(comments, betSlipID);
		}
	},

	updateComments: function(comments, betSlipID) {
		var items = this.$el.find('.newsfeed-item');
		for (var ct = 0; ct < items.length; ct++) {
			var $item  = $(items[ct]);
			var data   = $item.data('newsfeedData');
			if (!data)                         {continue;}
			if (data.bet_slip_id != betSlipID) {continue;}
			data.comments = comments;
			$item.find('.newsfeed-item-footer-comments-button .badge').text(data.comments.length);
			$item.find('.newsfeed-item-comments-div').empty();
			for (var ct2 = 0; ct2 < data.comments.length; ct2++) {
				var $comment = $($('#dashboard-newsfeed-comment-container', this.$el).html());
				$comment.data('commentData', data.comments[ct2]);

				$comment.find('.newsfeed-comment-name').text(data.comments[ct2].name);
				$comment.find('.newsfeed-comment-date').text(moment(data.comments[ct2].modified).fromNow());
				$comment.find('.newsfeed-comment-text').text(data.comments[ct2].comment);

				if (data.comments[ct2].user_id == window.UserData.user_id) {
					$comment.find('.newsfeed-comment-delete').show();
				}

				$item.find('.newsfeed-item-comments-div').append($comment);
			}
		}
	},

	updateLikes: function(likes, betSlipID) {
		var items = this.$el.find('.newsfeed-item');
		for (var ct = 0; ct < items.length; ct++) {
			var $item  = $(items[ct]);
			var data   = $item.data('newsfeedData');
			if (!data)                         {continue;}
			if (data.bet_slip_id != betSlipID) {continue;}
			data.likes = likes;
			$item.find('.newsfeed-item-footer-like-button').removeClass('btn-default').removeClass('btn-primary');
			$item.find('.newsfeed-item-footer-like-button').addClass('btn-default');
			$item.find('.newsfeed-item-footer-like-button .badge').text(likes.length);
			for (var ct2 = 0; ct2 < likes.length; ct2++) {
				if (likes[ct2].user_id == window.UserData.user_id) {
					$item.find('.newsfeed-item-footer-like-button').removeClass('btn-default').addClass('btn-primary');
				}
			}
		}
	},

	loadDashboard: function() {
		Utility.Ajax.request({
			mode:     'Dashboard::getNewsfeed',
			callback: $.proxy(onGetBetSlipsSuccess, this)
		});

		function onGetBetSlipsSuccess(result) {
			if (!result.success)     {return;}
			if (!result.data.length) {return;}

			this.loadNewsfeed(result.data);
		}
	},

	getNewsfeedItemTitle: function(data) {
		var headerName = "";
		if (data.created == data.modified) {
			headerName = "Created a new bet slip"
		}
		else {
			headerName = "Modified their bet slip"
		}

		return headerName;
	},

	getNewsfeedItem: function(data) {
		var $item       = $($('#dashboard-newsfeed-item-container', this.$el).html());
		var $itemHeader = $item.find('.newsfeed-item-header');
		$item.append($itemHeader);

		$item.find('.newsfeed-item-header-name').text(data.name);
		$item.find('.newsfeed-item-header-date').text(moment(data.modified).fromNow());
		$item.find('.newsfeed-item-header-title').text(this.getNewsfeedItemTitle(data));
		$item.data('newsfeedData', data);

		// Likes
		$item.find('.newsfeed-item-footer-like-button .badge').text(data.likes.length);
		for (var ct = 0; ct < data.likes.length; ct++) {
			if (data.likes[ct].user_id == window.UserData.user_id) {
				$item.find('.newsfeed-item-footer-like-button').removeClass('btn-default').addClass('btn-primary');
			}
		}

		// Comments
		$item.find('.newsfeed-item-footer-comments-button .badge').text(data.comments.length);

		$item.find('.newsfeed-item-comments-div').empty();
		for (var ct2 = 0; ct2 < data.comments.length; ct2++) {
			var $comment = $($('#dashboard-newsfeed-comment-container', this.$el).html());
			$comment.data('commentData', data.comments[ct2]);

			$comment.find('.newsfeed-comment-name').text(data.comments[ct2].name);
			$comment.find('.newsfeed-comment-date').text(moment(data.comments[ct2].modified).fromNow());
			$comment.find('.newsfeed-comment-text').text(data.comments[ct2].comment);

			if (data.comments[ct2].user_id == window.UserData.user_id) {
				$comment.find('.newsfeed-comment-delete').show();
			}

			$item.find('.newsfeed-item-comments-div').append($comment);
		}

		// Data
		var $data         = $item.find('.newsfeed-item-body-container');
		var tagsJSON      = data.tags;
		var $tagContainer = $('<div style="display:inline-block">');
		var tags          = $.parseJSON(tagsJSON);
		for (var ct = 0; ct < tags.length; ct++) {
			$tagContainer.append($('<div style="display:inline-block">').append($('<div>').text(tags[ct]).addClass('newsfeed-tag-item-text')));
		}
		$data.prepend($tagContainer);

		$data.find('.newsfeed-data-record').text(data.record);

		$data.append(this.getBetSlipText(data));

		return $item;
	},

	getBetSlipText: function(data) {
		var bets           = data.bets;
		var $mainContainer = $('<div>');
		for (var ct = 0; ct < bets.length; ct++) {
			var $container = $($('#dashboard-newsfeed-data-container', this.$el).html());
			var matches    = $.parseJSON(bets[ct].matches);
			for (var ct2 = 0; ct2 < matches.length; ct2++) {
				var $container2 = $($('#dashboard-newsfeed-matches-container', this.$el).html());
				$container2.find('.newsfeed-data-match-sport')  .html('<b>'+this.getSportName(matches[ct2].sport_id)+'</b>');
				$container2.find('.newsfeed-data-match-matchup').text(matches[ct2].match_up_one+' vs. '+matches[ct2].match_up_two);
				$container2.find('.newsfeed-data-match-pick')   .text(matches[ct2].pick);
				$container2.find('.newsfeed-data-match-odds')   .text('('+matches[ct2].odds+')');
				$container.find('.newsfeed-data-bet-matches').append($container2);
			}
			$container.find('.newsfeed-data-bet')    .text(bets[ct].units_bet+'U');
			$mainContainer.append($container);
		}

		return $mainContainer;
	},

	getSportName: function(sportID) {
		for (var ct = 0; ct < this.sports.length; ct++) {
			if (this.sports[ct].sport_id == sportID) {return this.sports[ct].name;}
		}
	},

	loadNewsfeed: function(rows) {
		var $dashboard   = $('#dashboard-newsfeed', this.$el);
		for (var ct = 0; ct < rows.length; ct++) {
			$dashboard.append(this.getNewsfeedItem(rows[ct]));
		}
	}
});

//# sourceURL=DashboardController.js