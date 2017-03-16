(function(Utility) {

	// Utility.createActionsMenu = function(actions) {
	// 	// Example:
	// 	// var $menu = Utility.createActionsMenu({
	// 	//     'Draw Border':       function() {},
	// 	//     'Draw Inner Border': function() {}
	// 	// });

	// 	var $list = $('<ul class="dropdown-menu">');

	// 	Utility.addActions($list, actions || []);

	// 	var $menu = $('<div>')
	// 		.addClass('btn-group')
	// 		.append(
	// 			$('<a class="grid-actions btn dropdown-toggle">')
	// 				.addClass('')
	// 				.attr('data-toggle', 'dropdown')
	// 				.attr('href', '#')
	// 				.text('Actions')
	// 				.append(
	// 					$('<span>').addClass('caret')))
	// 		.append($list);

	// 	return $menu;
	// };

	// Utility.addActions = function($list, actions) {
	// 	for (var key in actions)
	// 	{
	// 		if (actions.hasOwnProperty(key))
	// 		{
	// 			var className = 'actions-' + key.replace(/\ /g, '-').toLowerCase();

	// 			$list.append(
	// 				$('<li>')
	// 					.addClass(className)
	// 					.click(actions[key])
	// 					.append(
	// 						$('<a href="#">').text(key)));
	// 		}
	// 	}
	// };

	// Utility.getComputedStyle = function(domElement) {
	// 	var style = {};

	// 	if ('getComputedStyle' in window)
	// 	{
	// 		var computedStyle = window.getComputedStyle(domElement, '');
	// 		if (computedStyle.length === 0)
	// 		{
	// 			// Special case for Opera
	// 			$.each(computedStyle, function(property, value) {
	// 				style[property] = value;
	// 			});
	// 		}
	// 		else
	// 		{
	// 			// This covers everything but Opera and IE
	// 			$.each(computedStyle, function(index, property) {
	// 				style[property] = computedStyle.getPropertyValue(property);
	// 			});
	// 		}
	// 	}
	// 	else if ('currentStyle' in domElement)
	// 	{
	// 		// This covers IE
	// 		$.each(domElement.currentStyle, function(property, value) {
	// 			style[property] = value;
	// 		});
	// 	}

	// 	return style;
	// };

	// Utility.Notify = {
	// 	pnotify: function(type, title, msg, options, bCheckDuplicate) {
	// 		if (title && !msg)
	// 		{
	// 			msg   = title;
	// 			title = null;
	// 		}

	// 		// Make sure we aren't putting up multiple messages with the same text
	// 		var currentMessages = $(window).data('pnotify') || [];
	// 		var bDuplicate      = false;

	// 		if (currentMessages.length && bCheckDuplicate)
	// 		{
	// 			$.each(currentMessages, function(currentMessagesIndex, currentMessage){
	// 				var $currentMessage = $(currentMessage);
	// 				if (!$currentMessage.is(':visible')){return;}

	// 				if ($('.ui-pnotify-title', $currentMessage).text() == title && $('.ui-pnotify-text', $currentMessage).text() == msg){bDuplicate = true;return false;}
	// 			});

	// 			if (bDuplicate){return;}
	// 		}

	// 		var $notice = $.pnotify(
	// 			$.extend({
	// 				type:  type,
	// 				title: title,
	// 				text:  msg,
	// 				addclass: 'stack-bar-bottom',
	// 				width: '40%'
	// 			}, options || {})
	// 		);

	// 		// pNotify is stupid about its helper functions, and only stores them on *this* jQuery result instance.
	// 		$notice.data('pnotify_functions', $notice);

	// 		if (!title)
	// 		{
	// 			$('.ui-pnotify-title', $notice).hide();
	// 		}

	// 		return $notice;
	// 	},

	// 	alert: function(title, msg, options, bCheckDuplicate) {
	// 		return Utility.Notify.pnotify(null, title, msg, options, bCheckDuplicate);
	// 	},

	// 	info: function(title, msg, options, bCheckDuplicate) {
	// 		return Utility.Notify.pnotify('info', title, msg, options, bCheckDuplicate);
	// 	},

	// 	success: function(title, msg, options, bCheckDuplicate) {
	// 		return Utility.Notify.pnotify('success', title, msg, options, bCheckDuplicate);
	// 	},

	// 	error: function(title, msg, options, bCheckDuplicate) {
	// 		return Utility.Notify.pnotify('error', title, msg, options, bCheckDuplicate);
	// 	}
	// };

	Utility.DataSource = {
		getSports: function(callback) {
			if (Utility.DataSource.sports) {callback(Utility.DataSource.sports);return;}
			Utility.Ajax.request({
				mode:     'Sport::getAll',
				callback: $.proxy(onGetAllSportsSuccess, this)
			});

			function onGetAllSportsSuccess(result) {
				if (!result.success) {return;}

				Utility.DataSource.sports = result.data;

				callback(result.data);
			}
		}
	};

	Utility.Array = {
		sum: function(items, key) {
			var sum = 0;
			$.each(items, function(index, item) {sum += key ? item[key] : item;});
			return sum;
		},

		stringify: function(arrayOfStrings2, connectorWord) {
			if (!connectorWord) {connectorWord = 'and';}
			var arrayOfStrings = arrayOfStrings2.slice(0);

			// If there aren't any items, something went wrong :(... hopefully this doesn't happen
			if (!arrayOfStrings.length){return false;}

			// If there is only one thing just return it
			if (arrayOfStrings.length === 1){return arrayOfStrings[0];}

			// If there are exactly TWO items put an and in between them and return them
			if (arrayOfStrings.length === 2){return arrayOfStrings.join(' '+connectorWord+' ');}

			// Lastly, if there are more than two items, separate them with commas and the last item should be ", and last item"
			// Splice out the last one
			var lastItem    = arrayOfStrings.splice(arrayOfStrings.length - 1, 1);
			var commaString = arrayOfStrings.join(', ');

			return commaString + ', '+connectorWord+' ' + lastItem;
		}
	};

	Utility.String = {
		uniqid: function(prefix) {
			Utility.String.uniqidCounter = (Utility.String.uniqidCounter || 0) + 1;
			return (prefix || '') + Utility.String.uniqidCounter;
		},

		ucwords: function(str) {
			return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
				return $1.toUpperCase();
			});
		},

		repeat: function(str, n) {
			var newStr = '';
			for (var i = 0; i < n; i++) {newStr += str;}
			return newStr;
		}
	};

	Utility.Number = {
		round: function(value, decimals, force, commas) {
			var factor = 1;
			for (var i = 0; i < (decimals === undefined ? 2 : decimals); i++) {factor *= 10;}
			value = Math.round(value * factor) / factor;

			if (force)
			{
				value += ((value+'').indexOf('.') == -1 ? '.' : '') + '0000000000000000000000';
				value  = value.slice(0, (value.indexOf('.')) + 3);
			}

			return (commas ? Utility.Number.addCommas(value) : value);
		},

		addCommas: function(value) {
			var parts = (value+'').split('.');
			parts[0] = parts[0].replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
			value = parts[1] ? parts[0] + '.' + parts[1] : parts[0];
			return value;
		},

		monetize: function(value) {
			return '$' + Utility.Number.round(value, 2, true, true);
		}
	};

	// Utility.DataTables = {
	// 	// ***********************
	// 	// *  SELECTION HELPERS  *
	// 	// ***********************
	// 	hasSelection: function(table) {
	// 		return !!Utility.DataTables.getSelected(table)[0];
	// 	},

	// 	hasSelections: function(table) {
	// 		return Utility.DataTables.hasSelection(table);
	// 	},

	// 	getSelected: function(table) {
	// 		var selectedData = TableTools.fnGetInstance(table[0]).fnGetSelectedData();
	// 		return selectedData;
	// 	},

	// 	removeSelected: function(table) {
	// 		TableTools.fnGetInstance(table[0]).fnRemoveSelected();
	// 	},

	// 	selectAll: function(table) {
	// 		TableTools.fnGetInstance(table[0]).fnSelectAll();
	// 	},

	// 	clearSelections: function(table) {
	// 		TableTools.fnGetInstance(table[0]).fnSelectNone();
	// 	},

	// 	// *************************
	// 	// *  GETTERS AND SETTERS  *
	// 	// *************************
	// 	addData: function(table, data) {
	// 		table.fnAddData(data);
	// 		table.fnDraw();
	// 		table.fnAdjustColumnSizing();
	// 	},

	// 	setData: function(table, data) {
	// 		table.fnClearTable();
	// 		Utility.DataTables.addData(table, data);
	// 	},

	// 	clearData: function(table) {
	// 		Utility.DataTables.setData(table, []);
	// 	},

	// 	getData: function(table, row) {
	// 		var data = TableTools.fnGetInstance(table[0]).fnGetDataObject();

	// 		if (row === undefined)
	// 		{
	// 			return data;
	// 		}
	// 		else
	// 		{
	// 			return data[row];
	// 		}
	// 	},

	// 	removeRow: function(table, row) {
	// 		return table.fnDeleteRow(row);
	// 	},

	// 	// **********
	// 	// *  MISC  *
	// 	// **********
	// 	create: function($element, config) {
	// 		config = $.extend({}, {
	// 			bDeferRender: true,
	// 			bJQueryUI:    true,
	// 			sScrollY:    '290px',
	// 			sScrollX:    '90%',
	// 			sDom:        'TrtS',
	// 			oTableTools: {aButtons: []},
	// 			aaData:      [],
	// 			aoColumns:   []
	// 		}, config);

	// 		var table = $element.dataTable(config);

	// 		// Be nice and fix the columns while we're at it
	// 		Utility.DataTables.fixColumns(table);

	// 		return table;
	// 	},

	// 	fixColumns: function(table) {
	// 		table.fnAdjustColumnSizing();
	// 		return table;
	// 	},

	// 	sort: function(table, dataIndex) {
	// 		// Find the column specified by the dataIndex
	// 		var aoColumns = table.fnSettings().aoColumns;
	// 		var columnNum = null;

	// 		for (var i = 0; i < aoColumns.length; i++)
	// 		{
	// 			if (aoColumns[i].sDataIndex === dataIndex)
	// 			{
	// 				columnNum = i;
	// 				break;
	// 			}
	// 		}

	// 		if (columnNum)
	// 		{
	// 			table.fnSort([[columnNum, 'asc']]);
	// 		}
	// 	}
	// };

	Utility.Module = {
		launch: function(config, callback, target) {
			// Allow passing in just the path
			if (config === (config+'')) {config = {path: config};}

			var name       = config.path.split('/').pop();
			var path       = config.path;
			var file       = 'js/modules/'+(path||name)+'/'+name+'.html';
			config.options = config.options || {};

			var $target = target ? $(target) : $('#main-container');

			$.get(file, function(html) {
				$target.append(html);
				if (config.fade) {$target.find('.'+name).last().hide();}

				// Instantiate controller
				$target.find('.'+name)[name+'Controller'](config.options);

				var module = $target.find('.'+name).last().controller();

				if (callback) {
					// The initiating entity wants us to notify it when this module has launched.
					callback(module);
				}

				if (config.fade) {$target.find('.'+name).last().fadeIn('fast');}
			}, 'html').error(function(xhr, status, error) {
				// Utility.Notify.error('Module Load Error', 'There was an error loading the '+name+' module. Please contact support.');
			});
		},

		destroy: function(name) {
			$('.'+name)[name+'Controller']('destroy');

			// Find and destroy any nested controllers
			$('.'+name).find('[class$="Controller"]').each(function(index, item) {
				var $controllerEl = $(item);

				$.each($controllerEl.data('controllers'), function(controllerName, item) {
					$controllerEl[controllerName]('destroy');
				});
			});

			$('.'+name).empty();
		},

		destroyAll: function() {
			// Find and destroy any nested controllers
			$('.tab-content').find('[class*="Controller"]').each(function(index, item) {
				var $controllerEl = $(item);

				if ($controllerEl.data('controllers') && $controllerEl.data('controllers').length) {
					$.each($controllerEl.data('controllers'), function(controllerName, item) {
						$controllerEl[controllerName]('destroy');
					});
				}

				$controllerEl.empty();
			});
		}
	};

	Utility.Ajax = {
		request: function(config) {
			var callback = config.callback;
			delete config.callback;

			// Add json_ prefix where necessary
			for (var key in config) {
				if (config.hasOwnProperty(key)) {
					if (typeof config[key] == 'object') {
						config['json_'+key] = JSON.stringify(config[key]);
						delete config[key];
					}
				}
			}

			$.ajax({
				data: config,
				success: callback
			});
		}
	};

	// Utility.Modal = {
	// 	alert: function(msg, header, callback, okText) {
	// 		// Support passing in a selector or plain text to $body
	// 		if (msg instanceof $)
	// 		{
	// 			msg = msg.html();
	// 		}

	// 		return bootbox.alert(
	// 			{
	// 				message: '<form>'+msg+'</form>',
	// 				title: header || '',
	// 				buttons: {
	// 					ok: {
	// 						label: okText || 'OK',
	// 						callback: callback
	// 					}
	// 				},
	// 				callback: callback
	// 			});
	// 	},

	// 	prompt: function(msg, callback, defaultValue) {
	// 		// Support passing in a selector or plain text to $body
	// 		if (msg instanceof $)
	// 		{
	// 			msg = msg.html();
	// 		}

	// 		return bootbox.prompt(
	// 			{
	// 				message: '<form>'+msg+'</form>',
	// 				value: defaultValue || '',
	// 				buttons: {
	// 					confirm: {
	// 						label: 'OK',
	// 						callback: callback
	// 					},
	// 					cancel: {
	// 						label: 'Cancel'
	// 					}
	// 				},
	// 				className: "testclass"
	// 			}
	// 		);
	// 	},

	// 	confirm: function(header, msg, callback, confirmText, cancelText) {
	// 		// Support passing in a selector or plain text to $body
	// 		if (msg instanceof $)
	// 		{
	// 			msg = msg.html();
	// 		}

	// 		return bootbox.confirm(
	// 			{
	// 				message: '<form>'+msg+'</form>',
	// 				title: header || '',
	// 				buttons: {
	// 					confirm: {
	// 						label: confirmText || 'OK',
	// 						callback: callback
	// 					},
	// 					cancel: {
	// 						label: cancelText || 'Cancel'
	// 					}
	// 				},
	// 				callback: callback
	// 			}
	// 		);
	// 	}
	// };

}(window.Utility = window.Utility || {}));

function numberDecimalOnly(e)
{
	var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
}