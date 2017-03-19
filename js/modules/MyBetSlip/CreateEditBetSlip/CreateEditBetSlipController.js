/**
 * Class: CreateEditBetSlip
 * Controller for CreateEditBetSlip.
 */
$.Controller('CreateEditBetSlip', {pluginName: 'CreateEditBetSlipController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		// Load units from local storage
		this.loadUnitConversion();

		var self = this;
		Utility.DataSource.getSports(function(data) {
			self.sports = data;

			if (self.options.betSlip) {
				self.loadData(self.options.betSlip);
			}
		});

		this.currentTags = [];
		Utility.DataSource.getTags(function(data) {
			self.tags = data;

			$("#tag-search", this.$el).typeahead({source: self.tags, afterSelect: $.proxy(self.onTagSelected, self)});

			if (self.options.betSlip) {
				self.loadTags(self.options.betSlip.tags);
			}
		});
	},

	initEvents: function() {
		this.$el.on('click', '#save-button',         $.proxy(this.onSaveButtonClick,   this));
		this.$el.on('click', '#add-bet-button',      $.proxy(this.onAddBetButtonClick, this));
		this.$el.on('click', '#delete-button',       $.proxy(this.onDeleteButtonClick, this));
		this.$el.on('click', '.outcome-btn',         $.proxy(this.toggleOutComeButton, this));
		this.$el.on('keyup', '#money-to-unit-input', $.proxy(this.updateAmounts,       this));
		this.$el.on('click', '.tag-item-delete',     $.proxy(this.onTagDeleted,        this));
	},

	loadTags: function(tagsJSON) {
		if (!tagsJSON) {return;}

		var tags = $.parseJSON(tagsJSON);
		for (var ct = 0; ct < tags.length; ct++) {
			this.onTagSelected(tags[ct]);
		}
	},

	onTagDeleted: function(evt) {
		var $button = $(evt.target);
		var tagName = $button.parent().find('.tag-item-text').text();
		for (var ct = 0; ct < this.currentTags.length; ct++) {
			if (this.currentTags[ct] == tagName) {this.currentTags.splice(ct, 1);}
		}
		$button.parent().remove();
	},

	onTagSelected: function(tag) {
		// See if this tag exists yet
		var bFound = false;
		for (var ct = 0; ct < this.currentTags.length; ct++) {
			if (this.currentTags[ct] == tag) {bFound = true;}
		}

		// If it's already there, just clear the field and return
		if (bFound) {$("#tag-search", this.$el).val('');return;}

		// Else add it and make a tag entry
		this.currentTags.push(tag);
		$('#tags-container', this.$el).append($('<div>').addClass('tag-item-container').append($('<div>').text(tag).addClass('tag-item-text'),$('<div>').text('x').addClass('tag-item-delete')));
		$("#tag-search", this.$el).val('');
	},

	updateAmounts: function() {
		var conversion                                = $('#money-to-unit-input', this.$el).val();
		window.localStorage.reddit_sb_unit_conversion = conversion;
		var dollars                                   = this.$el.find('.amount-bet,.amount-win');
		for (var ct  = 0; ct < dollars.length; ct++) {
			var $dollar = $(dollars[ct]);
			$dollar.val(this.unitToDollarConverted($dollar.val()));
		}
	},

	loadUnitConversion: function() {
		var unitsConversion = null;

		// See if we are editing or creating
		if (this.options.betSlip) {
			// Attempt to find the bet slip in localstorage
			if (window.localStorage.bet_slips == null) {
				window.localStorage.bet_slips = "{}";
			}
			var currentBetSlips = $.parseJSON(window.localStorage.bet_slips);
			unitsConversion     = (currentBetSlips[this.options.betSlip.bet_slip_id]) ? currentBetSlips[this.options.betSlip.bet_slip_id].reddit_sb_unit_conversion : null;
		}

		if (!unitsConversion) {
			// If this is a new one, just pull the last used units conversion
			unitsConversion = (window.localStorage.reddit_sb_unit_conversion) ? window.localStorage.reddit_sb_unit_conversion : null;
		}

		// Just clear out if nothing was found
		if (!unitsConversion) {return;}

		// Go ahead and input it
		$('#money-to-unit-input', this.$el).val(unitsConversion);
	},

	toggleOutComeButton: function(evt) {
		var $button  = $(evt.target);
		var $buttons = $button.parent().find('.outcome-btn').removeClass('btn-success').removeClass('btn-danger').addClass('btn-default');
		if ($button.text() == 'LOSS') {$button.removeClass('btn-default');$button.addClass('btn-danger'); }
		if ($button.text() == 'WIN')  {$button.removeClass('btn-default');$button.addClass('btn-success');}
	},

	onDeleteButtonClick: function() {
		Utility.confirm("Are you sure you want to delete this Bet Slip?").then($.proxy(continueDelete, this));
		
		function continueDelete(yesno) {
			if (yesno != 'yes') {return;}
			Utility.Ajax.request({
				mode:        'BetSlip::delete',
				bet_slip_id: this.options.betSlip.bet_slip_id,
				callback:    $.proxy(onDeleteBetSlipSuccess, this)
			});
		}

		function onDeleteBetSlipSuccess(result) {
			if (!result.success) {$.notify({message: result.msg},{type: 'danger'});return;}

			$.notify({
				message: result.msg
			},{
				type: 'success'
			});

			this.$el.parent().trigger('dialogclose');
		}
	},

	loadData: function(data) {
		var self = this;
		setTimeout(function() {
			self.$el.find('#delete-button').show();
		}, 100);
		
		if (data.public !== 'f') {
			this.$el.find('#public').prop('checked', true);
		}
		else {
			this.$el.find('#public').prop('checked', false);
		}
		this.$el.find('#notes').val(data.notes);
		this.loadBets(data.bets);
	},

	loadBets: function(bets) {
		var $table = $('#bet-table', this.$el);
		var self   = this;
		$.each(bets, function(index, bet) {
			var $row   = $('<tr>');

			var $matchupTD = $('<td>');
			var matches    = $.parseJSON(bet.matches);
			for (var ct = 0; ct < matches.length; ct++) {
				self.addMatchupToRow($matchupTD, true, matches[ct]);
			}
			$row.append($matchupTD);

			$row.append($('<td>').append($('<input>').addClass('amount-bet')));
			$row.append($('<td>').append($('<input>').addClass('amount-win')));

			// Outcome
			$row.append($('<td>').append(
				'<div class="btn-group btn-toggle" data-toggle="buttons">'+
					'<button class="outcome-btn btn btn-sm btn-default">WIN</button>'+
				    '<button class="outcome-btn btn btn-sm btn-default focus active">TBD</button>'+
				    '<button class="outcome-btn btn btn-sm btn-default">LOSS</button>'+
				'</div>'
			));

			$row.append('<button id="remove-bet" type="button" class="btn btn-danger">'+
	  		'<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>'+
			'</button>');

			$table.append($row);

			$row.find('.amount-bet').val(self.unitToDollarConverted(bet.units_bet));
			$row.find('.amount-win').val(self.unitToDollarConverted(bet.units_to_win));

			var $buttons = $row.find('.outcome-btn');
			$.each($buttons, function(index, button) {
				$(button).removeClass('active');
				$(button).removeClass('focus');
			});

			if (bet.outcome == 'WIN') {
				$(".outcome-btn:contains('WIN')", $row).trigger('click');
			}
			else if (bet.outcome == 'LOSS') {
				$(".outcome-btn:contains('LOSS')", $row).trigger('click');
			}
			else {
				$(".outcome-btn:contains('TBD')", $row).trigger('click');	
			}

			$row.find('#remove-bet') .on('click', $.proxy(self.onRemoveBetRowClick, self));
			$row.find('.outcome-btn').on('click', $.proxy(toggleButtons,            self));
			$row.on('click', '.add-matchup-button', function() {self.addMatchupToRow($matchupTD);});

			function toggleButtons(evt) {
				var $button  = $(evt.target);
				$button.addClass('active');
				var $buttons = $row.find('.outcome-btn');
				$.each($buttons, function(index, button) {
					if ($(button).text() != $button.text()) {$(button).removeClass('active');$(button).removeClass('focus');}
				});
			}
		});
	},

	onRemoveBetRowClick: function(evt) {
		$(evt.target).closest('tr').remove();
	},

	validateBetSlipData: function(data) {
		var isValid = true;
		var message = [];
		if (!data.tags || data.tags == '[]')  {isValid = false;message.push("Please enter at least one tag for your bet slip.");}
		if (!data.bets.length)                {isValid = false;message.push("Please include bets on your bet slip.");}
		if (!$('#money-to-unit-input', this.$el).val()) {$('#money-to-unit-input', this.$el).parent().addClass('has-error');isValid = false;message.push("Please enter a dollar amount per unit.");}
		var missingInfoBets = false;
		for (var ct = 0; ct < data.bets.length; ct++) {
			for (var ct2 = 0; ct2 < data.bets[ct].matches.length; ct2++) {
				var match = data.bets[ct].matches[ct2];
				if (!match.match_up_one) {missingInfoBets = true;}
				if (!match.match_up_two) {missingInfoBets = true;}
				if (!match.sport_id)     {missingInfoBets = true;}
				if (!match.pick)         {missingInfoBets = true;}
				if (!match.odds)         {missingInfoBets = true;}
			}
			if (!data.bets[ct].units_bet)    {missingInfoBets = true;}
			if (!data.bets[ct].units_to_win) {missingInfoBets = true;}
		}
		if (missingInfoBets) {isValid = false;message.push("Please include all information on your bets.");}

		if (!isValid) {
			$.notify({
				message: 'Please fix the following errors before clicking save: '+Utility.Array.stringify(message)
			},{
				type: 'danger'
			});

			return false;
		}
		else {
			return true;
		}
	},

	onSaveButtonClick: function() {
		var betSlipData  = this.getBetSlipData();
		var betSlipValid = this.validateBetSlipData(betSlipData);
		if (!betSlipValid) {return;}

		Utility.Ajax.request({
			mode:        'BetSlip::saveBetSlip',
			data:        betSlipData,
			date:        this.options.date,
			bet_slip_id: (this.options.betSlip) ? this.options.betSlip.bet_slip_id : null,
			callback:    $.proxy(onSaveBetSlipDataSuccess, this)
		});

		function onSaveBetSlipDataSuccess(result) {
			if (!result.success) {$.notify({message: result.msg},{type: 'danger'});return;}
			
			// Save the units conversion
			if (window.localStorage.bet_slips == null) {
				window.localStorage.bet_slips = "{}";
			}

			window.localStorage.reddit_sb_unit_conversion = $('#money-to-unit-input', this.$el).val();
			var currentBetSlips                           = $.parseJSON(window.localStorage.bet_slips);
			currentBetSlips[result.data]                  = {reddit_sb_unit_conversion: $('#money-to-unit-input', this.$el).val()};
			window.localStorage.bet_slips                 = JSON.stringify(currentBetSlips);

			$.notify({
				message: result.msg
			},{
				type: 'success'
			});

			this.$el.parent().trigger('dialogclose');
		}
	},

	getBets: function() {
		var bets = [];
		var rows = $('#bet-table tr', this.$el);
		for (var ct = 1; ct < rows.length; ct++) {
			var $row          = $(rows[ct]);
			var rowData       = {};
			var matchups      = $row.find('.matchup-row, .matchup-row-margin');
			rowData.matches   = [];
			for (var ct2 = 0; ct2 < matchups.length; ct2++) {
				var $matchup         = $(matchups[ct2]);
				var matchup          = {};
				matchup.sport_id     = $matchup.find('.sports-select').val();
				matchup.match_up_one = $matchup.find('.match-up-one').val();
				matchup.match_up_two = $matchup.find('.match-up-two').val();
				matchup.pick         = $matchup.find('.pick').val();
				matchup.odds         = $matchup.find('.odds').val();
				matchup.notes        = $matchup.find('.bet-notes').val();
				rowData.matches.push(matchup);
			}

			rowData.units_bet    = this.dollarToUnitConverted($row.find('.amount-bet').val());
			rowData.units_to_win = this.dollarToUnitConverted($row.find('.amount-win').val());
			var $buttons      = $row.find('.outcome-btn');
			$.each($buttons, function(index, button) {
				var $button = $(button);
				if ($button.hasClass('active')) {rowData.outcome = $button.text();}
			});
			bets.push(rowData);
		}

		return bets;
	},

	getBetSlipData: function() {
		var betSlipData    = {};
		betSlipData.tags   = JSON.stringify(this.currentTags);
		betSlipData.public = this.$el.find('#public').is(':checked');
		betSlipData.bets   = this.getBets();

		return betSlipData;
	},

	onAddBetButtonClick: function() {
		var $table = $('#bet-table', this.$el);
		var $row   = $('<tr>');
		var self   = this;

		var $matchupTD    = $('<td>');
		this.addMatchupToRow($matchupTD, true);
		$row.append($matchupTD);

		$row.append($('<td>').append($('<input>').addClass('amount-bet')));
		$row.append($('<td>').append($('<input>').addClass('amount-win')));

		// Outcome
		$row.append($('<td>').append(
			'<div class="btn-group btn-toggle" data-toggle="buttons">'+
				'<button class="outcome-btn btn btn-sm btn-default">WIN</button>'+
			    '<button class="outcome-btn btn btn-sm btn-default focus active">TBD</button>'+
			    '<button class="outcome-btn btn btn-sm btn-default">LOSS</button>'+
			'</div>'
		));

		$row.append('<button id="remove-bet" type="button" class="btn btn-danger">'+
  		'<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>'+
		'</button>');

		$table.append($row);

		$row.find('#remove-bet')        .on('click', $.proxy(this.onRemoveBetRowClick, this));
		$row.find('.outcome-btn')       .on('click', $.proxy(toggleButtons,            this));
		$row.on('click', '.add-matchup-button', function() {self.addMatchupToRow($matchupTD);});

		function toggleButtons(evt) {
			var $button  = $(evt.target);
			$button.addClass('active');
			var $buttons = $row.find('.outcome-btn');
			$.each($buttons, function(index, button) {
				if ($(button).text() != $button.text()) {$(button).removeClass('active');$(button).removeClass('focus');}
			});
		}
	},

	addMatchupToRow: function($row, isFirst, data) {
		var $mtdContainer = $('<div>').addClass((isFirst) ? 'matchup-row' : 'matchup-row-margin');
		$row.append($mtdContainer);
		var self = this;

		// Sport
		var $sportSelect = $('<select>').addClass('sports-select');
		$.each(this.sports, function(index, sport) {
			$sportSelect.append($('<option>').text(sport.name).val(sport.sport_id));
		});
		
		$mtdContainer.append($sportSelect);

		// Match up
		$mtdContainer.append(
			$('<input>').addClass('match-up-one'),
			$('<span>').text(' vs.'),
			$('<input>').addClass('match-up-two')
		);

		// Pick
		$mtdContainer.append($('<input placeholder="Pick">').addClass('pick'));

		// Odds
		$mtdContainer.append($('<input placeholder="Odds">').addClass('odds'));

		// Add new line button
		$mtdContainer.append($('<button>').addClass('add-matchup-button').text('Add').addClass('btn btn-success btn-sm'));

		// Add new line button
		$mtdContainer.append($('<br><span>').html('<b>Notes:</b>'), $('<textarea>').addClass('bet-notes'));

		if (data) {
			$sportSelect.val(data.sport_id);
			$mtdContainer.find('.match-up-one').val(data.match_up_one);
			$mtdContainer.find('.match-up-two').val(data.match_up_two);
			$mtdContainer.find('.pick').val(data.pick);
			$mtdContainer.find('.odds').val(data.odds);
			$mtdContainer.find('.bet-notes').val(data.notes);
		}
	},

	dollarToUnitConverted(dollar) {
		var dollarPerUnit = $('#money-to-unit-input', this.$el).val()*1;
		if (!dollarPerUnit) {return dollar;}
		return dollar/dollarPerUnit;
	},

	unitToDollarConverted(units) {
		var dollarPerUnit = $('#money-to-unit-input', this.$el).val()*1;
		if (!dollarPerUnit) {return units;}
		return units*dollarPerUnit;
	}
});

//# sourceURL=CreateEditBetSlipController.js