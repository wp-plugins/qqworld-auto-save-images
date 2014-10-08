function str_repeat(i, m) {
	for (var o = []; m > 0; o[--m] = i);
	return o.join('');
}
function sprintf() {
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

if (!QQWorld_auto_save_images) var QQWorld_auto_save_images = {};
QQWorld_auto_save_images.scan_posts = function() {
	var _this = this,
	$ = jQuery;

	this.action = {};
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
		$('#form').slideDown('slow');
		$('body').data('noty').close();
		noty({
			text: error,	
			type: 'error',
			layout: 'bottom',
			dismissQueue: true,
			closeWith: ['button']
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
			$('#form').slideDown('slow');
			$('body').data('noty').close();
			var count = $('#scan_old_post_list tbody tr').length;
			var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
			var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
			var count = $('#scan_old_post_list tbody tr').length;
			if (count) {
				if (count==1) count_html = sprintf(QASI.n_post_has_been_scanned, count);
				else count_html = sprintf(QASI.n_posts_have_been_scanned, count);
				if (count_remote_images) {
					count_remote_images = count_remote_images - count_not_exits_remote_images;
					if (count_remote_images<=1) count_html += sprintf("<br />"+QASI.n_post_included_remote_images_processed, count_remote_images);
					else count_html += sprintf("<br />"+QASI.n_posts_included_remote_images_processed, count_remote_images);
					if (count_not_exits_remote_images) {
						if (count_not_exits_remote_images==1) count_html += sprintf("<br />"+QASI.n_post_has_missing_images_couldnt_be_processed, count_not_exits_remote_images);
						else count_html += sprintf("<br />"+QASI.n_posts_have_missing_images_couldnt_be_processed, count_not_exits_remote_images);
					}
				} else count_html += '<br />'+QASI.no_posts_processed;
			} else {
				$('#scan_old_post_list').slideUp('slow');
				count_html = QASI.no_posts_found;
			}
			noty({
				text: QASI.all_done+'<br />'+count_html,	
				type: 'success',
				layout: 'center',
				dismissQueue: true,
				modal: true
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
			$('#form').slideDown('slow');
			$('body').data('noty').close();
			var count = $('#scan_old_post_list tbody tr').length;
			var count_remote_images = $('#scan_old_post_list tbody tr.has_remote_images').length;
			var count_not_exits_remote_images = $('#scan_old_post_list tbody tr.has_not_exits_remote_images').length;
			if (count) {
				if (count==1) count_html = sprintf(QASI.n_post_has_been_scanned, count);
				else count_html = sprintf(QASI.n_posts_have_been_scanned, count);
				if (count_remote_images) {
					if (count_remote_images==1) count_html += sprintf("<br />"+QASI.found_n_post_including_remote_images, count_remote_images);
					else count_html += sprintf("<br />"+QASI.found_n_posts_including_remote_images, count_remote_images);
					if (count_not_exits_remote_images) {
						if (count_not_exits_remote_images==1) count_html += sprintf("<br />"+QASI.and_with_n_post_has_missing_images, count_not_exits_remote_images);
						else count_html += sprintf("<br />"+QASI.and_with_n_posts_have_missing_images, count_not_exits_remote_images);
					}
				} else count_html += '<br />'+QASI.no_post_has_remote_images_found;
			} else {
				$('#scan_old_post_list').slideUp('slow');
				count_html = QASI.no_posts_found;
			}
			noty({
				text: QASI.all_done+'<br />'+count_html,	
				type: 'success',
				layout: 'center',
				dismissQueue: true,
				modal: true
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
			timeout: 3000
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
			if (jQuery('input[name="qqworld_auto_save_imagess_post_types[]"]:checked').length) {
				var n = noty({
					text: QASI.are_your_sure,	
					type: 'warning',
					dismissQueue: true,
					layout: 'center',
					modal: true,
					buttons: [
						{
							addClass: 'button button-primary',
							text: QASI.yes,
							onClick: function ($noty) {
								$('#form').slideUp('slow');
								$noty.close();
								$('#scan_old_posts').attr('disabled', true);
								$('#list_all_posts').attr('disabled', true);
								var data = $('#form').serialize()+'&action=get_scan_list';
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
											text: QASI.scanning,	
											type: 'notification',
											layout: 'center',
											dismissQueue: true
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
			if (jQuery('input[name="qqworld_auto_save_imagess_post_types[]"]:checked').length) {
				$('#form').slideUp('slow');
				$('#scan_old_posts').attr('disabled', true);
				$('#list_all_posts').attr('disabled', true);
				var data = $('#form').serialize()+'&action=get_scan_list';
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
							text: QASI.listing,	
							type: 'notification',
							layout: 'center',
							dismissQueue: true
						}) );
						_this.action.list(respond, 0);
					},
					error: _this.action.catch_errors
				});
			} else _this.action.if_not_select_post_type();
		});
		$(document).on('click', '#scan_old_post_list .fetch-remote-images', function() {
			var wait = '<img src="data:image/gif;base64,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" />';
			var post_id = $(this).attr('post-id');
			$(this).hide().after(wait);
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
		})
	};

	this.create.init = function() {
		_this.create.events();
	};
	this.create.init();
};