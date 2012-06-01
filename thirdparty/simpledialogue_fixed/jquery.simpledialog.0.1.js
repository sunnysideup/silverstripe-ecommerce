/**
 * jQuery Simple Dialog Plugin
 *   http://code.google.com/p/jquery-simpledialog/
 *
 * Copyright (c) 2009 Yusuke Horie
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Since  : 0.01 - 07/06/2009
 * Version: 0.11 - 08/08/2009
 */
(function(jQuery) {

	// Private Variables

	var
		_doc = null,
		_win = null,
		_docHeight = 0,
		_winHeight = 0,
		_winWidth = 0,
		_t = null,
		_event = null,
		_target = null,
		_escapedContent = '',
		_title = '',
		_caption = '',
		_container = null,
		_content = null;

	// Public Methods

	jQuery.fn.simpleDialog = function (options) {
		var opts = jQuery.extend({}, jQuery.fn.simpleDialog.defaults, options);

		return this.each(
			function(i, e) {
				var jQuerythis = jQuery(e);
				if(jQuerythis.hasClass("simpleDialogueBound")) {
					return;
				}
				jQuerythis.addClass("simpleDialogueBound");
				var triggerEvent = (jQuerythis.is('form')) ? 'submit': 'click';
				jQuerythis
					.bind(triggerEvent + '.simpledialog', function (event) {
						event.preventDefault();

						_t = this;

						_t.opts = opts;
						_event = event;

						_initialize();

						// show overlay
						_prepare();

						var jQueryt = jQuery(this);

						if (_t.opts.title != '')
							_title = _t.opts.title;

						if (jQueryt.is('a')) {
							if (_t.opts.useTitleAttr) {
								var title = jQueryt.attr('title');
								if (typeof title != 'undefined' && title != '')
									_title = title;
							}

							var href = jQueryt.attr('href');

							if (href.match(/^#/)) {
								var c = jQuery('#' + jQueryt.attr('rel'));
								if (c.length == 0) return false;
								_target = c;
								_escapedContent = _target.clone().html();
								_target.empty();
								_show(_escapedContent);
							} else if (jQueryt.find('img').length > 0) {
								if (_t.opts.showCaption)
									_caption = jQueryt.find('img').attr('title');
								_load(href);
							} else {
								_request(href, {});
							}
						} else if (jQueryt.is(':submit', ':button')) {
							var f = jQueryt.parents('form');
							_request(f.attr('action'), f.serialize(), f.attr('method'));
						} else if (jQueryt.is('form')) {
							_request(jQueryt.attr('action'), jQueryt.serialize(), jQueryt.attr('method'));
						} else {
							jQuery.simpleDialog.close(event);
						}
						return false;
					}
				);
			}
		);
	};

	jQuery.fn.simpleDialog.defaults = {
		title: '',
		useTitleAttr: true,
		containerId: 'sd_container',
		containerClass: 'sd_container',
		overlayId: 'sd_overlay',
		overlayClass: 'sd_overlay',
		loadingClass: 'sd_loading',
		closeLabelClass: 'sd_closelabel',
		showCloseLabel: true,
		closeLabel: 'close &times;',
		opacity: 0.6,
		duration: 400,
		easing: 'linear',
		zIndex: 1000,
		width: null,
		height: null,
		showCaption: true,
		open: null,
		close: null,
		closeSelector: '.close'
	};

	jQuery.simpleDialog = {
		close: function (event) {
			if (jQuery.isFunction(_t.opts.close))
				_t.opts.close.apply(this, [(typeof event == 'undefined') ? null: event, _t]);
			if (_container != null)
				_container.remove();
			if (_target != null)
				_target.html(_escapedContent);
			jQuery('#' + _t.opts.overlayId).remove();
			jQuery(document).unbind("keyup", _escKeyEvent);
			return false;
		}

	};

	// Private Methods

	var _initialize = function () {
		_doc = jQuery(document);
		_win = jQuery(window);
		_docHeight = _doc.height();
		_winHeight = _win.height();
		_winWidth = _win.width();
		_title = '';
		_caption = '';
	};

	var _escKeyEvent = function(e) {
		if (e.keyCode == 27) {
			jQuery.simpleDialog.close();
		}
	}

	var _show = function (content) {
		jQuery(document).keyup(_escKeyEvent);

		var body = '<div id="'+_t.opts.containerClass+'_inner"><div id="'+_t.opts.containerClass+'_inner_inner">';

		if (_title != '')
			body += '<div class="sd_header">' + _title + '</div>';

		body += '<div class="sd_content">' + content + '</div>';

		if (_caption != '' && typeof _caption != 'undefined')
			body += ' <div class="sd_footer">' + _caption + '</div>';

		body += "</div></div>";

		var tmp = jQuery('<div />')
			.addClass(_t.opts.containerClass)
			.hide()
			.css({
				position: 'absolute',
				height: 'auto'
			})
			.html(body)
			.appendTo(document.body);

		var w = (_t.opts.width) ? parseInt(_t.opts.width) : tmp.width();
		var h = (_t.opts.height) ? parseInt(_t.opts.height) : tmp.height();
		w = w * 1.1
		h = h * 1.1
		tmp.remove();
		var pos = _center(w, h);


		_container
			.removeClass(_t.opts.loadingClass)
			.animate({
				width: w + 'px',
				height: h + 'px',
				left: pos[0] + 'px',
				top: pos[1] + 'px'
			}, _t.opts.duration, _t.opts.easing, function() {
				_container
					.html(body)
					.find(_t.opts.closeSelector)
					.bind('click.simpledialog', jQuery.simpleDialog.close);

				if (_t.opts.showCloseLabel) {
					var sc = '<div id="sd_closelabel" class="' + _t.opts.closeLabelClass + '">' +
						'<a href="#">' + _t.opts.closeLabel + '</a></div>';

					_container.hover(
						function () {
							jQuery(this).append(sc);
							var scObj = jQuery('#sd_closelabel');
							scObj
								.css({
									position: 'absolute',
									top: 0,
									right: '30px',
									opacity: 0.85
								})
								.find('a').click(jQuery.simpleDialog.close);
						},
						function () { jQuery('#sd_closelabel').remove() });
				}

				if (jQuery.isFunction(_t.opts.open))
					_t.opts.open.apply(_container, [_event, _t]);
			});
	};

	var _request = function (url, data, method) {
		jQuery.ajax({
			type: (typeof method == 'undefined') ? 'GET': method,
			url: url,
			data: data,
			dataType: 'html',
			success: _show
		});
	};

	var _load = function (url) {
		jQuery(document.body)
			.append('<div id="sd_tmp_image" style="display:none;"><img src="' + url + '" alt="" /></div>');

		var tmp = jQuery('#sd_tmp_image');
		tmp.find('img')
			.load(function () {
				var h = tmp.html();
				tmp.remove();
				_show(h);
			})
			.error(function (e) {
				jQuery.simpleDialog.close(e);
				tmp.remove();
			});
	};

	var _prepare = function () {

		// overlay
		jQuery('<div />')
			.attr('id', _t.opts.overlayId)
			.addClass(_t.opts.overlayClass)
			.css({
				position: 'absolute',
				width: _winWidth,
				height: _docHeight,
				opacity: _t.opts.opacity,
				zIndex: _t.opts.zIndex
			})
			.bind('click.simpledialog', jQuery.simpleDialog.close)
			.appendTo(document.body);

		// container
		_container = jQuery('<div />')
			.attr('id', _t.opts.containerId)
			.addClass(_t.opts.loadingClass)
			.addClass(_t.opts.containerClass)
			.hide()
			.appendTo(document.body);

		var w = _container.width();
		var h = _container.height();
		var pos = _center(w, h);

		_container
			.css({
				position: 'absolute',
				left: pos[0] + 'px',
				top: pos[1] + 'px',
				width: w + 'px',
				height: h + 'px',
				zIndex: _t.opts.zIndex + 1000
			})
			.show();
	};

	var _center = function (w, h) {
		return [(_docHeight > _winHeight) ? _winWidth/2 - w/2 - 18: _winWidth/2 - w/2,
			_doc.scrollTop() + _winHeight/2 - h/2];
	};

})(jQuery);
