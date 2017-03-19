/**
 * Class: MyBetSlip
 * Controller for MyBetSlip.
 */
$.Controller('MyBetSlip', {pluginName: 'MyBetSlipController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		// Init today's date
		this.currentDate = moment();
		$("#datepicker", this.$el).datepicker({onSelect: $.proxy(this.onDatePickerSelect, this)});

		this.dateUpdated();
	},

	initEvents: function() {
		this.$el.on('click', '#previous-date', $.proxy(this.onPreviousDateClick, this));
		this.$el.on('click', '#next-date',     $.proxy(this.onNextDateClick,     this));
		this.$el.on('click', '#current-date',  $.proxy(this.onDatePickerClick,   this));
		this.$el.on('click', '#create-new',    $.proxy(this.onCreateNewClick,    this));
		this.$el.on('click', 'tr',             $.proxy(this.onBetSlipClick,      this));
	},

	onBetSlipClick: function(evt) {
		var rowData = $(evt.target).parent('tr').data('rowData');
		if (!rowData) {return;}

		this.onCreateNewClick(null, rowData);
	},

	onCreateNewClick: function(evt, betSlip) {
		var $dialog = $($('#create-new-bet-slip-html').html()).dialog();
		var self    = this;

		// Make sure it destroys instances on close
		$dialog.on('dialogclose', function(event) {
			$dialog.dialog('destroy');
			self.getBetSlips();
		});

		var windowHeight = $(window).height();
		var windowWidth  = $(window).width();
		var dialogWidth  = 1400;
		if (windowWidth <= 1068) {
			dialogWidth  = 1000;
		}
		else if (windowWidth <= 1280) {
			dialogWidth = 1200;
		}
		
		var dialogHeight = (windowHeight <= 800)  ? 650  : 800;

		// Center dialog
		$dialog.parent('.ui-dialog').css('height', dialogHeight);
		$dialog.parent('.ui-dialog').css('width',  dialogWidth);
		$dialog.parent('.ui-dialog').css('top',    (windowHeight/2) - (dialogHeight/2));
		$dialog.parent('.ui-dialog').css('left',   (windowWidth/2)  - (dialogWidth/2) );
		$dialog.css('height', 'calc(100% - 33px)');

		if (betSlip) {
			$dialog.dialog('option', 'title', 'Edit Bet Slip');
		}

		// Init the controller to the div
		Utility.Module.launch({path: 'MyBetSlip/CreateEditBetSlip', options: {betSlip: betSlip, date: this.currentDate.format('YYYY-MM-DD')}}, $.noop, $dialog);

		$dialog.on('click', '#cancel-button', function() {$dialog.dialog('destroy');});
	},

	onDatePickerClick: function() {
		$("#datepicker", this.$el).datepicker('setDate', this.currentDate.format('MM/DD/YYYY'));
		$("#datepicker", this.$el).datepicker('show');
	},

	onDatePickerSelect: function(date) {
		this.currentDate = moment(date);

		this.dateUpdated();
	},

	onPreviousDateClick: function() {
		this.currentDate = this.currentDate.subtract('1', 'day');

		this.dateUpdated();
	},

	onNextDateClick: function() {
		this.currentDate = this.currentDate.add('1', 'day');	

		this.dateUpdated();
	},

	dateUpdated: function() {
		if (this.currentDate.format('YYYY-MM-DD') == moment().format('YYYY-MM-DD')) {
			$('#current-date', this.$el).text('Today');
		}
		else {
			$('#current-date', this.$el).text(this.currentDate.format('YYYY-MM-DD'));	
		}

		this.getBetSlips();
	},

	getBetSlips: function() {
		var rows = $('#bet-slip-table tr');
		for (var ct = 1; ct < rows.length; ct++) {
			$(rows[ct]).remove();
		}

		Utility.Ajax.request({
			mode:     'BetSlip::getByDate',
			date:     this.currentDate.format('YYYY-MM-DD'),
			callback: $.proxy(onGetBetSlipsSuccess, this)
		});

		function onGetBetSlipsSuccess(result) {
			if (!result.success)     {return;}
			if (!result.data.length) {return;}

			this.loadTable(result.data);
		}
	},

	getTagsHTML: function(tagsJSON) {
		if (!tagsJSON || tagsJSON == '[]') {return '';}
		var $tagContainer = $('<div style="display:inline-block">');
		var tags          = $.parseJSON(tagsJSON);
		for (var ct = 0; ct < tags.length; ct++) {
			$tagContainer.append($('<div style="display:inline-block">').append($('<div>').text(tags[ct]).addClass('bet-slip-tag-item-text')));
		}

		return $tagContainer;
	},

	loadTable: function(data) {
		var $table          = $('#bet-slip-table');

		var self            = this;
		var totalTotalUnits = 0;
		var totalPlusMinus  = 0;
		$.each(data, function(index, betSlip) {
			var $row       = $('<tr>');
			var totalUnits = self.getTotalUnits(betSlip.bets);
			var plusMinus  = self.getPlusMinus(betSlip.bets);
			$row.append($('<td>').append(self.getTagsHTML(betSlip.tags)));
			$row.append($('<td>').append((betSlip.public === 't') ? 'YES' : 'NO'));
			$row.append($('<td>').append(betSlip.bets.length));
			$row.append($('<td>').append(totalUnits));
			$row.append($('<td>').append(plusMinus));

			$table.append($row);

			$row.data('rowData', betSlip);

			totalTotalUnits += totalUnits*1;
			totalPlusMinus  += plusMinus*1;
		});

		var $row = $('<tr class="info">');
		$row.append($('<td>').append(''));
		$row.append($('<td>').append(''));
		$row.append($('<td>').append(''));
		$row.append($('<td>').html('<b>'+totalTotalUnits+'</b>'));
		$row.append($('<td>').html('<b>'+totalPlusMinus+'</b>'));

		$table.append($row);
	},

	getPlusMinus: function(bets) {
		var plusMinus = 0;
		$.each(bets, function(index, bet) {
			if (bet.outcome == 'WIN') {
				plusMinus += bet.units_to_win*1;
			}
			else if (bet.outcome == 'LOSS') {
				plusMinus -= bet.units_bet*1;
			}
		});

		return plusMinus;
	},

	getTotalUnits: function(bets) {
		var totalUnits = 0;
		$.each(bets, function(index, bet) {
			totalUnits += bet.units_bet*1;
		});

		return totalUnits;
	}
});

//# sourceURL=MyBetSlipController.js