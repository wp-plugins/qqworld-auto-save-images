if (!QQWorld_auto_save_images) var QQWorld_auto_save_images = {};
QQWorld_auto_save_images.scan_posts = function() {
	var _this = this,
	$ = jQuery,
	noty_theme = typeof qqworld_ajax == 'object' ? 'qqworldTheme' : 'defaultTheme',
	wait_img = '<img src=" data:image/gif;base64,R0lGODlhgAAPAKIAALCvsMPCwz8/PwAAAPv6+wAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh+QQECgAAACwAAAAAgAAPAAAD50ixS/6sPRfDpPGqfKv2HTeBowiZGLORq1lJqfuW7Gud9YzLud3zQNVOGCO2jDZaEHZk+nRFJ7R5i1apSuQ0OZT+nleuNetdhrfob1kLXrvPariZLGfPuz66Hr8f8/9+gVh4YoOChYhpd4eKdgwAkJEAE5KRlJWTD5iZDpuXlZ+SoZaamKOQp5wEm56loK6isKSdprKotqqttK+7sb2zq6y8wcO6xL7HwMbLtb+3zrnNycKp1bjW0NjT0cXSzMLK3uLd5Mjf5uPo5eDa5+Hrz9vt6e/qosO/GvjJ+sj5F/sC+uMHcCCoBAAh+QQECgAAACwAAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALAsAAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsFgAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACwhAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALCwAAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsNwAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACxCAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALE0AAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsWAAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACxjAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALG4AAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAseQAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAA7" />';

	this.file_frame;

	this.watermark = {};
	this.image = {};
	this.offset = {
		top: {
			half: 0,
			full: 0
		},
		left: {
			half: 0,
			full: 0
		}
	};

	this.lib = {};
	this.lib.sprintf = function() {
		var str_repeat = function(i, m) {
			for (var o = []; m > 0; o[--m] = i);
			return o.join('');
		}
		var i = 0, a, f = arguments[i++], o = [], m, p, c, x, s = '';
		while (f) {
			if (m = /^[^\x25]+/.exec(f)) {
				o.push(m[0]);
			}
			else if (m = /^\x25{2}/.exec(f)) {
				o.push('%');
			}
			else if (m = /^\x25(?:(\d+)\$)?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(f)) {
				if (((a = arguments[m[1] || i++]) == null) || (a == undefined)) {
					throw('Too few arguments.');
				}
				if (/[^s]/.test(m[7]) && (typeof(a) != 'number')) {
					throw('Expecting number but found ' + typeof(a));
				}
				switch (m[7]) {
					case 'b': a = a.toString(2); break;
					case 'c': a = String.fromCharCode(a); break;
					case 'd': a = parseInt(a); break;
					case 'e': a = m[6] ? a.toExponential(m[6]) : a.toExponential(); break;
					case 'f': a = m[6] ? parseFloat(a).toFixed(m[6]) : parseFloat(a); break;
					case 'o': a = a.toString(8); break;
					case 's': a = ((a = String(a)) && m[6] ? a.substring(0, m[6]) : a); break;
					case 'u': a = Math.abs(a); break;
					case 'x': a = a.toString(16); break;
					case 'X': a = a.toString(16).toUpperCase(); break;
				}
				a = (/[def]/.test(m[7]) && m[2] && a >= 0 ? '+'+ a : a);
				c = m[3] ? m[3] == '0' ? '0' : m[3].charAt(1) : ' ';
				x = m[5] - String(a).length - s.length;
				p = m[5] ? str_repeat(c, x) : '';
				o.push(s + (m[4] ? a + p : p + a));
			}
			else {
				throw('Huh ?!');
			}
			f = f.substring(m[0].length);
		}
		return o.join('');
	}

	this.action = {};
	
	this.action.set_watermark_opacity = function() {
		var opacity = $('#watermark-opacity').val();
		$('#watermark-test').fadeTo('normal', opacity/100);
	};
	this.action.get_watermark_size = function() {
		_this.watermark.width = $('#watermark-test').width();
		_this.watermark.height = $('#watermark-test').height();
		_this.image.width = $('#photo-test').width();
		_this.image.height = $('#photo-test').height();
		_this.offset.top.full = parseInt(_this.image.height - _this.watermark.height);
		_this.offset.left.full = parseInt(_this.image.width - _this.watermark.width);
		_this.offset.top.half = parseInt(_this.offset.top.full/2);
		_this.offset.left.half = parseInt(_this.offset.left.full/2);
	};

	this.action.catch_errors = function(XMLHttpRequest, textStatus, errorThrown) {
		var error='', args=new Array;
		error += '<div style="text-align: left;">';
		var query = this.data.split('&');
		var data = new Array;
		var offset_from_id = $('input[name="offset"]').val();
		var temp_r = $('body').data('r') + parseInt(offset_from_id);
		for (var d in query) {
			var q = query[d].split('=');
			if (q[0]=='post_id[]') {
				temp_r++;
				data.push(q[1]+'(No. '+temp_r+')');
			}
		}
		error += QASI.maybe_problem + data.join(', ');
		if (XMLHttpRequest) {
			error += '<hr />';
			args = new Array;
			for (var x in XMLHttpRequest) {
				switch (x) {
					case 'readyState':
					case 'responseText':
					case 'status':
						args.push( x + ': ' + XMLHttpRequest[x] );
						break;
				}
			}
			error += args.join('<br />', args);
		}
		error += '<br />' + textStatus + ': ' + errorThrown;
		error += '</div>';
		$('body').data('noty').close();
		noty({
			text: error,	
			type: 'error',
			layout: 'bottom',
			dismissQueue: true,
			closeWith: ['button'],
			theme: noty_theme
		});
		$('#scan_old_posts').removeAttr('disabled');
		$('#list_all_posts').removeAttr('disabled');
		$('body').data('r', $('body').data('r')+$('body').data('speed'));
		switch ($('body').data('scan-mode')) {
			case 'scan':
				_this.action.scan($('body').data('respond'), $('body').data('r'));
				break;
			case 'list':
				_this.action.list($('body').data('respond'), $('body').data('r'));
				break;
		}
	};
	this.action.scan = function(respond, r) {
		var $ = jQuery;
		$('body').data('scan-mode', 'scan').data('r', r);
		if (typeof respond[r] == 'undefined') {
			$('#scan-result').effect( 'shake', null, 500 );
			$('#scan-post-block').slideDown('normal');
			$('body').data('noty').close();
			var count = $('#scan_old_post_list tbody tr').length;
			var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
			var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
			var count = $('#scan_old_post_list tbody tr').length;
			if (count) {
				if (count==1) count_html = _this.lib.sprintf(QASI.n_post_has_been_scanned, count);
				else count_html = _this.lib.sprintf(QASI.n_posts_have_been_scanned, count);
				if (count_remote_images) {
					count_remote_images = count_remote_images - count_not_exits_remote_images;
					if (count_remote_images<=1) count_html += _this.lib.sprintf("<br />"+QASI.n_post_included_remote_images_processed, count_remote_images);
					else count_html += _this.lib.sprintf("<br />"+QASI.n_posts_included_remote_images_processed, count_remote_images);
					if (count_not_exits_remote_images) {
						if (count_not_exits_remote_images==1) count_html += _this.lib.sprintf("<br />"+QASI.n_post_has_missing_images_couldnt_be_processed, count_not_exits_remote_images);
						else count_html += _this.lib.sprintf("<br />"+QASI.n_posts_have_missing_images_couldnt_be_processed, count_not_exits_remote_images);
					}
				} else {
					$('#scan_old_post_list').slideUp('slow');
					count_html += '<br />'+QASI.no_posts_processed;
				}
			} else {
				$('#scan_old_post_list').slideUp('slow');
				count_html = QASI.no_posts_found;
			}
			noty({
				text: QASI.all_done+'<br />'+count_html,	
				type: 'success',
				layout: 'center',
				dismissQueue: true,
				modal: true,
				theme: noty_theme
			});
			$('#scan_old_posts').removeAttr('disabled');
			$('#list_all_posts').removeAttr('disabled');
			return;
		}
		var speed = parseInt($('select[name="speed"]').val());
		post_id = new Array;
		$('body').data('speed', speed);
		var data = 'action=save_remote_images_after_scan';
		for (var p=r; p<r+speed; p++) {
			if (typeof respond[p] != 'undefined') data += '&post_id[]='+respond[p];
		}
		console.log(data);
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data,
			success: function(data) {
				data = $(data);
				$('#scan_old_post_list tbody').append(data);
				data.hide().fadeIn('fast');
				r += speed;
				_this.action.scan(respond, r);
			},
			error: _this.action.catch_errors
		});
	};
	this.action.list = function(respond, r) {
		var $ = jQuery;
		$('body').data('scan-mode', 'list').data('r', r);
		if (typeof respond[r] == 'undefined') {
			$('#scan-result').effect( 'shake', null, 500 );
			$('#scan-post-block').slideDown('normal');
			$('body').data('noty').close();
			var count = $('#scan_old_post_list tbody tr').length;
			var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
			var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
			if (count) {
				if (count==1) count_html = _this.lib.sprintf(QASI.n_post_has_been_scanned, count);
				else count_html = _this.lib.sprintf(QASI.n_posts_have_been_scanned, count);
				if (count_remote_images) {
					if (count_remote_images==1) count_html += _this.lib.sprintf("<br />"+QASI.found_n_post_including_remote_images, count_remote_images);
					else count_html += _this.lib.sprintf("<br />"+QASI.found_n_posts_including_remote_images, count_remote_images);
					if (count_not_exits_remote_images) {
						if (count_not_exits_remote_images==1) count_html += _this.lib.sprintf("<br />"+QASI.and_with_n_post_has_missing_images, count_not_exits_remote_images);
						else count_html += _this.lib.sprintf("<br />"+QASI.and_with_n_posts_have_missing_images, count_not_exits_remote_images);
					}
				} else {
					$('#scan_old_post_list').slideUp('slow');
					count_html += '<br />'+QASI.no_post_has_remote_images_found;
				}
			} else {
				$('#scan_old_post_list').slideUp('slow');
				count_html = QASI.no_posts_found;
			}
			noty({
				text: QASI.all_done+'<br />'+count_html,	
				type: 'success',
				layout: 'center',
				dismissQueue: true,
				modal: true,
				theme: noty_theme
			});
			$('#scan_old_posts').removeAttr('disabled');
			$('#list_all_posts').removeAttr('disabled');
			return;
		}
		var speed = parseInt($('select[name="speed"]').val());
		post_id = new Array;
		$('body').data('speed', speed);
		var data = 'action=save_remote_images_list_all_posts';
		for (var p=r; p<r+speed; p++) {
			if (typeof respond[p] != 'undefined') data += '&post_id[]='+respond[p];
		}
		console.log(data);
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data,
			success: function(data) {
				data = $(data);
				$('#scan_old_post_list tbody').append(data);
				data.hide().fadeIn('fast');
				r += speed;
				_this.action.list(respond, r);
			},
			error: _this.action.catch_errors
		});
	};


	this.action.if_not_select_post_type = function() {
		var $ = jQuery;
		$('#post_types_list').effect( 'shake', null, 500 );
		var n = noty({
			text: QASI.pls_select_post_types,	
			type: 'error',
			dismissQueue: true,
			layout: 'bottomCenter',
			timeout: 3000,
			theme: noty_theme
		});
	}

	this.create = {};
	this.create.events = function() {
		$(".icon.help").tooltip({
			show: {
				effect: "slideDown",
				delay: 250
			}
		});
		$('select[name="posts_per_page"]').on('change', function() {
			if ($(this).val() == '-1') $('input[name="offset"]').attr('disabled', true);
			else $('input[name="offset"]').removeAttr('disabled', true);
		});
		$('#auto').on('click', function() {
			$('#second_level').fadeIn('fast');
		});
		$('#manual').on('click', function() {
			$('#second_level').fadeOut('fast');
		});
		$('#scan_old_posts').on('click', function() {
			if (jQuery('input[name="qqworld_auto_save_images_post_types[]"]:checked').length) {
				var n = noty({
					text: QASI.are_your_sure,	
					type: 'warning',
					dismissQueue: true,
					layout: 'center',
					modal: true,
					theme: noty_theme,
					buttons: [
						{
							addClass: 'button button-primary',
							text: QASI.yes,
							onClick: function ($noty) {
								$('#scan-post-block').slideUp('normal');
								$noty.close();
								$('#scan_old_posts').attr('disabled', true);
								$('#list_all_posts').attr('disabled', true);
								var data = $('#scan').serialize()+'&action=get_scan_list';
								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: data,
									dataType: 'json',
									success: function(respond) {
										$('body').data('respond', respond);
										$('#scan-result').html('<table id="scan_old_post_list">\
										\	<thead>\
										\		<th>' + QASI.id + '</th>\
										\		<th>' + QASI.post_type + '</th>\
										\		<th>' + QASI.title + '</th>\
										\		<th>' + QASI.status + '</th>\
										\	</thead>\
										\	<tbody>\
										\	</tbody>\
										\</table>');
										$('body').data('noty', noty({
											text: wait_img+' &nbsp; '+QASI.scanning,	
											type: 'notification',
											layout: 'center',
											dismissQueue: true,
											theme: noty_theme
										}) );
										_this.action.scan(respond, 0);
									},
									error: _this.action.catch_errors
								});
							}
						},
						{
							addClass: 'button button-primary',
							text: QASI.no,
							onClick: function ($noty) {
								$noty.close();
							}
						}
					]
				});
			} else _this.action.if_not_select_post_type();
		});
		$('#list_all_posts').on('click', function() {
			if (jQuery('input[name="qqworld_auto_save_images_post_types[]"]:checked').length) {
				$('#scan-post-block').slideUp('normal');
				$('#scan_old_posts').attr('disabled', true);
				$('#list_all_posts').attr('disabled', true);
				var data = $('#scan').serialize()+'&action=get_scan_list';
				console.log(data);
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: data,
					dataType: 'json',
					success: function(respond) {
						$('body').data('respond', respond);
						$('#scan-result').html('<table id="scan_old_post_list">\
						\	<thead>\
						\		<th>' + QASI.id + '</th>\
						\		<th>' + QASI.post_type + '</th>\
						\		<th>' + QASI.title + '</th>\
						\		<th>' + QASI.status + '</th>\
						\		<th>' + QASI.control + '</th>\
						\	</thead>\
						\	<tbody>\
						\	</tbody>\
						\</table>');
						$('body').data('noty', noty({
							text: wait_img+' &nbsp; '+QASI.listing,	
							type: 'notification',
							layout: 'center',
							dismissQueue: true,
							theme: noty_theme
						}) );
						_this.action.list(respond, 0);
					},
					error: _this.action.catch_errors
				});
			} else _this.action.if_not_select_post_type();
		});
		$(document).on('click', '#scan_old_post_list .fetch-remote-images', function() {
			var post_id = $(this).attr('post-id');
			$(this).hide().after(wait_img);
			var data = 'action=save_remote_images_after_scan&post_id[]='+post_id;
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				success: function(data) {
					$('#list-'+post_id).html('<span class="green">'+QASI.done+'</span>');
				},
				error: _this.action.catch_errors
			});
		});
		$(document).on('click', '#add_exclude_domain', function() {
			var code = $('<li>http(s):// <input type="text" name="qqworld-auto-save-images-exclude-domain[]" class="regular-text" value="" /><input type="button" class="button delete-exclude-domain" value="'+QASI.delete+'"></li>');
			if (!$('#exclude_domain_list li').length || $('#exclude_domain_list li.empty').length) $('#exclude_domain_list').html(code);
			else $('#exclude_domain_list').append(code);
			code.hide().slideDown('fast');
		});
		$(document).on('blur', '[name="qqworld-auto-save-images-exclude-domain[]"]', function() {
			var str = $(this).val();
			var re = new RegExp("^http(s)?:\/\/", 'i');
			if (re.test(str)) {
				var result =  str.match(re);
				noty({
					text: _this.lib.sprintf(QASI.no_need_enter_, result[0]),	
					type: 'error',
					layout: 'bottom',
					dismissQueue: true,
					theme: noty_theme,
					timeout: 5000
				});
				$(this).css({
					backgroundColor: '#f00',
					color: '#fff'
				});
			} else {
				$(this).removeAttr('style');
			}
		});
		$(document).on('click', '.delete-exclude-domain', function() {
			var parent = $(this).parent();
			parent.slideUp('fast', function() {
				parent.remove();
				if (!$('#exclude_domain_list li').length) $('#exclude_domain_list').append('<li class="empty"><input type="hidden" name="qqworld-auto-save-images-exclude-domain" value="" /></li>');
			});
		});
		$(document).on('click', '#qqworld-auto-save-images-tabs li', function() {
			if (!$(this).hasClass('current')) {
				var index = $('#qqworld-auto-save-images-tabs li').index(this);
				$('#qqworld-auto-save-images-tabs li').removeClass('current');
				$(this).addClass('current');
				$('.tab-content').hide().eq(index).fadeIn('normal');
			}
		});

		$(document).on('click', 'input[name="qqworld_auto_save_images_post_types[]"]', function() {
			var checked = $('input[name="qqworld_auto_save_images_post_types[]"]:checked');
			if (checked.length) {
				$('#categories_block').html(wait_img);
				var temp = '';
				checked.each(function() {
					temp += '&posttype[]=' + $(this).val();
				});
				var data = 'action=save_remote_images_get_categories_list'+temp;
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: data,
					success: function(data) {
						if (data.search(/<div>/)>0) {
							data = $(data);
							$('#categories_block').html(data);
							data.hide().fadeIn('normal');
						} else $('#categories_block').html(data);
					},
					error: _this.action.catch_errors
				});
			} else {
				$('#categories_block').html(QASI.pls_select_post_types);
			}
		});

		$(document).on('change', '#optimize-mode', function() {
			if ($(this).val() == 'remote') {
				$('#ftp-settings').fadeIn('normal');
				$('#protocol').fadeIn('normal');
				$('#folder').fadeIn('normal');
				$('#url_example').fadeIn('normal');
				$('#host').prev().fadeOut('normal');
				$('#host').next().fadeOut('normal');
			} else {
				$('#ftp-settings').fadeOut('normal');
				$('#protocol').fadeOut('normal');
				$('#folder').fadeOut('normal');
				$('#url_example').fadeOut('normal');
				$('#host').prev().fadeIn('normal');
				$('#host').next().fadeIn('normal');
			}
		});

		$(document).on('click', '#test-ftp', function() {
			var button = $(this);
			button.attr('disabled', true);
			$('body').data('noty', noty({
				text: wait_img,	
				type: 'notification',
				layout: 'center',
				theme: noty_theme
			}) );
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'auto_save_images_test_ftp'
				},
				dataType: 'json',
				success: function(respond) {
					$('body').data('noty').close();
					var options = {
						text: respond.msg,
						layout: 'center',
						timeout: 3000,
						theme: noty_theme
					};
					options.type = respond.success ? 'success' : 'error';
					var n = noty(options);
					button.removeAttr('disabled');
				},
				error: _this.action.catch_errors
			});
		});

		$(document).on('change', '#watermark-opacity', _this.action.set_watermark_opacity).on('keyup', '#watermark-opacity', _this.action.set_watermark_opacity);

		$(document).on('click', '#for-watermark-image', function() {
			$('#upload-watermark-image').click();
		});

		// watermark postion
		$(document).on('click', 'input[name="qqworld-auto-save-images-watermark-align-to"]', function() {
			var id = $(this).attr('id'),
			top, right, bottom, left,
			default_offset = 20,
			offset = {};
			switch (id) {
				case 'lt': offset.x = offset.y = default_offset; top = default_offset; left = default_offset; break;
				case 'ct': offset.x = 0; offset.y = default_offset; top = default_offset; left = _this.offset.left.half; break;
				case 'rt': offset.x = -default_offset; offset.y = default_offset; top = default_offset; left = _this.offset.left.full-default_offset; break;
				case 'lc': offset.x = default_offset; offset.y = 0; top = _this.offset.top.half; left = default_offset; break;
				case 'cc': offset.x = 0; offset.y = 0; top = _this.offset.top.half; left = _this.offset.left.half; break;
				case 'rc': offset.x = -default_offset; offset.y = 0; top = _this.offset.top.half; left = _this.offset.left.full-default_offset; break;
				case 'lb': offset.x = default_offset; offset.y = -default_offset; top = _this.offset.top.full-default_offset; left = default_offset; break;
				case 'cb': offset.x = 0; offset.y = -default_offset; top = _this.offset.top.full-default_offset; left = _this.offset.left.half; break;
				case 'rb': offset.x = -default_offset; offset.y = -default_offset; top = _this.offset.top.full-default_offset; left = _this.offset.left.full-default_offset; break;
			};
			$('#watermark-test').animate({
				top : top,
				left: left
			}, 'fast');
			$('#offset-x').val(offset.x);
			$('#offset-y').val(offset.y);
		});

		$(document).on('click', '#upload-watermark-image', function(event) {
			event.preventDefault();
			var title = $(this).attr('title'),
			id = $(this).attr('id');
			if ( typeof _this.file_frame == 'object' ) {
				_this.file_frame.open();
				return;
			}
			_this.file_frame = wp.media.frames.file_frame = wp.media({
				title: title,
				button: {
					text: title,
				},
				multiple: false
			});
			_this.file_frame.on( 'open', function() {
				var selection = _this.file_frame.state().get('selection');
				var attachment_id = $('input[name="qqworld-auto-save-images-watermark-image"]').val();
				if (attachment_id) {
					var attachment = wp.media.attachment(attachment_id);
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				}
			});
			_this.file_frame.on('select', function() {
				var attachment = _this.file_frame.state().get('selection').first().toJSON();
				var id = attachment.id;
				$('input[name="qqworld-auto-save-images-watermark-image"]').val(id);
				var url = attachment.url;
				$('#upload-watermark-image img').attr('src', url);
				$('#watermark-test').attr({
					src: url,
					width: attachment.sizes.full.width,
					height: attachment.sizes.full.height
				});
				$('#lt').click();
				_this.action.get_watermark_size();
				$('#default-watermark').fadeIn();
			});
			_this.file_frame.open();
		});

		$(document).on('click', '#default-watermark', function() {
			var src = QASI.default_watermark.src;
			$('#upload-watermark-image img').attr('src', src);
			$('#watermark-test').attr({
				src: src,
				width: QASI.default_watermark.width,
				height: QASI.default_watermark.height
			});
			$('input[name="qqworld-auto-save-images-watermark-image"]').val('');
			$('#lt').click();
			_this.action.get_watermark_size();
		});

		$(document).on('click', '#Preview Watermark', function() {
			tb_show($(this).attr('title'), $(this).attr('href'));
		});
		$(document).on('change', '#qqworld_auto_save_images_minimum_picture_size_width', function() {
			$('#qqworld_auto_save_images_minimum_picture_size_height').val($(this).val());
		});
	};

	this.create.watermark_init = function() {
		_this.action.get_watermark_size();
		$('#watermark-test').draggable({
			containment: "parent",
			drag: function() {
				var id = $('input[name="qqworld-auto-save-images-watermark-align-to"]:checked').val(),
				position = $('#watermark-test').position(),
				left = position.left,
				top = position.top,
				x,y;
				switch (id) {
					case 'lt': x = left; y = top; break;
					case 'ct': x = left-_this.offset.left.half; y = top; break;
					case 'rt': x = left-_this.offset.left.full; y = top; break;
					case 'lc': x = left; y = top-_this.offset.top.half; break;
					case 'cc': x = left-_this.offset.left.half; y = top-_this.offset.top.half; break;
					case 'rc': x = left-_this.offset.left.full; y = top-_this.offset.top.half; break;
					case 'lb': x = left; y = top-_this.offset.top.full; break;
					case 'cb': x = left-_this.offset.left.half; y = top-_this.offset.top.full; break;
					case 'rb': x = left-_this.offset.left.full; y = top-_this.offset.top.full; break;
				};
				//console.log('x:' + x + ', left: ' + left + ' - fullleft: ' + _this.offset.left.full);
				$('#offset-x').val(x);
				$('#offset-y').val(y);
			}
		});
		// set $('#watermark-test') position
		var id = $('input[name="qqworld-auto-save-images-watermark-align-to"]:checked').val(),
		left = parseInt(QASI.watermark_offset.x),
		top = parseInt(QASI.watermark_offset.y);
		switch (id) {
			case 'lt': x = left; y = top; break;
			case 'ct': x = left+_this.offset.left.half; y = top; break;
			case 'rt': x = left+_this.offset.left.full; y = top; break;
			case 'lc': x = left; y = top+_this.offset.top.half; break;
			case 'cc': x = left+_this.offset.left.half; y = top+_this.offset.top.half; break;
			case 'rc': x = left+_this.offset.left.full; y = top+_this.offset.top.half; break;
			case 'lb': x = left; y = top+_this.offset.top.full; break;
			case 'cb': x = left+_this.offset.left.half; y = top+_this.offset.top.full; break;
			case 'rb': x = left+_this.offset.left.full; y = top+_this.offset.top.full; break;
		};
		$('#watermark-test').css({ left: x, top: y });
		_this.action.set_watermark_opacity();
	};

	this.create.init = function() {
		_this.create.events();
		_this.create.watermark_init();
	};
	this.create.init();
};
jQuery(function($) {
	if ($('#post_types_list').length && $('#second_level').length) QQWorld_auto_save_images.scan_posts();
});