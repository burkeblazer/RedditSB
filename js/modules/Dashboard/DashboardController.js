/**
 * Class: Dashboard
 * Controller for Dashboard.
 */
$.Controller('Dashboard', {pluginName: 'DashboardController'}, {
	init: function() {
		this.$el = $(this.element);
		this.initEvents();

		// Default the start and end date
		if (moment().startOf('isoweek').format('YYYY-MM-DD') == moment().format('YYYY-MM-DD')) {
			this.$el.find('#dashboard-start-date').val(moment().startOf('isoweek').subtract(7, 'days').format('YYYY-MM-DD'));
			this.$el.find('#dashboard-end-date')  .val(moment().format('YYYY-MM-DD'));
		}
		else {
			this.$el.find('#dashboard-start-date').val(moment().startOf('isoweek').format('YYYY-MM-DD'));
			this.$el.find('#dashboard-end-date')  .val(moment().format('YYYY-MM-DD'));
		}
		
		$("#dashboard-start-date", this.$el).datepicker({dateFormat: 'yy-mm-dd', onSelect: $.proxy(this.loadData, this)});
		$("#dashboard-end-date",   this.$el).datepicker({dateFormat: 'yy-mm-dd', onSelect: $.proxy(this.loadData, this)});

		this.initChart();
	},

	initEvents: function() {
		this.$el.on('click', '.dropdown-menu a', function(evt) {var $button = $(evt.target);$('#dashboard-dropdown-text').text($button.text());});
	},

	initChart: function() {
		var ctx     = document.getElementById('dashboard-daily-chart').getContext('2d');
		this.dailyChart = new Chart(ctx, {
			type: 'bubble',
			options: {
				responsive:          true,
				maintainAspectRatio: false
			},
		  	data: {
		    	labels:   [],
		    	datasets: []
		  	}
		});

		this.loadData();
	},

	loadData: function() {
		this.dailyChart.data.labels   = [];
		this.dailyChart.data.datasets = [];
		this.dailyChart.update();

		Utility.Ajax.request({
			mode:       'Dashboard::getDailyChartDataByDate',
			start_date: this.$el.find('#dashboard-start-date').val(),
			end_date:   this.$el.find('#dashboard-end-date').val(),
			callback:   $.proxy(onGetByDate, this)
		});

		function onGetByDate(result) {
			if (!result.success) {return;}
			if (!result.data)    {return;}

			this.dailyChart.data.labels   = result.data.labels;
			for (var ct = 0; ct < result.data.data_series.length; ct++) {
				this.dailyChart.data.datasets.push({backgroundColor: this.dynamicColor(), label: result.data.data_series[ct]['label'], data: result.data.data_series[ct]['data']});
			}
			this.dailyChart.update();
		}
	},

	dynamicColor: function() {
	    var r = Math.floor(Math.random() * 255);
	    var g = Math.floor(Math.random() * 255);
	    var b = Math.floor(Math.random() * 255);
	    return "rgba(" + r + "," + g + "," + b + ", .3)";
	}
});

//# sourceURL=DashboardController.js