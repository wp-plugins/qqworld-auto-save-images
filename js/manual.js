jQuery(function($) {
	$(window).on('load', function() {
		var noty_theme = typeof qqworld_ajax == 'object' ? 'qqworldTheme' : 'defaultTheme',
		wait_img = '<img src=" data:image/gif;base64,R0lGODlhgAAPAKIAALCvsMPCwz8/PwAAAPv6+wAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh+QQECgAAACwAAAAAgAAPAAAD50ixS/6sPRfDpPGqfKv2HTeBowiZGLORq1lJqfuW7Gud9YzLud3zQNVOGCO2jDZaEHZk+nRFJ7R5i1apSuQ0OZT+nleuNetdhrfob1kLXrvPariZLGfPuz66Hr8f8/9+gVh4YoOChYhpd4eKdgwAkJEAE5KRlJWTD5iZDpuXlZ+SoZaamKOQp5wEm56loK6isKSdprKotqqttK+7sb2zq6y8wcO6xL7HwMbLtb+3zrnNycKp1bjW0NjT0cXSzMLK3uLd5Mjf5uPo5eDa5+Hrz9vt6e/qosO/GvjJ+sj5F/sC+uMHcCCoBAAh+QQECgAAACwAAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALAsAAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsFgAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACwhAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALCwAAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsNwAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACxCAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALE0AAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAsWAAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAAh+QQECgAAACxjAAAABwAPAAADEUiyq/wwyknjuDjrzfsmGpEAACH5BAQKAAAALG4AAAAHAA8AAAMRSLKr/DDKSeO4OOvN+yYakQAAIfkEBAoAAAAseQAAAAcADwAAAxFIsqv8MMpJ47g46837JhqRAAA7" />';

		$('.mce-i-save_remote_images').closest('.mce-widget').hide();
		$(document).on('click', '#save-remote-images-button', function() {
			var mode = 'text';
			if (tinyMCE.activeEditor) {
				var id = tinyMCE.activeEditor.id;
				mode = $('#'+id).is(':visible') ? 'text' : 'virtual';
			}
			var catch_error = function(XMLHttpRequest, textStatus, errorThrown) {
				console.log('XMLHttpRequest:');
				console.log(XMLHttpRequest);
				console.log('textStatus: ' + textStatus);
				console.log('errorThrown: ' + errorThrown);
			};
			switch (mode) {
				case 'text':
					$('#save-remote-images-button').data('noty', noty({
						text: wait_img + ' &nbsp; ' + QASI.in_process,	
						type: 'notification',
						layout: 'center',
						modal: true,
						closeWith: ['button'],
						theme: noty_theme
					}) );
					$.ajax({
						type: "POST",
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'save_remote_images',
							post_id: QASI.post_id,
							content: encodeURI(encodeURI($('#content').val()))
						},
						success: function(respond) {
							var options = {
								text: respond.msg,
								layout: 'center',
								theme: noty_theme
							};
							switch (respond.type) {
								case 1:
									options.type = 'warning';
									options.timeout = 3000;
									break;
								case 2:
									options.type = 'success';
									options.timeout = 3000;
									break;
								case 3:
									options.type = 'error';
									options.modal = true;
									break;
							}
							$('#save-remote-images-button').data('noty').close();
							var n = noty(options);
							if (respond.content) $('#content').val(respond.content);
						},
						error: catch_error
					});
					break;
				case 'virtual':
					$('#save-remote-images-button').data('noty', noty({
						text: wait_img + ' &nbsp; ' + QASI.in_process,	
						type: 'notification',
						layout: 'center',
						modal: true,
						closeWith: ['button'],
						theme: noty_theme
					}) );
					$.ajax({
						type: "POST",
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'save_remote_images',
							post_id: QASI.post_id,
							content: encodeURI(encodeURI(tinyMCE.activeEditor.getContent()))
						},
						success: function(respond) {
							switch (respond.type) {
								case 1: var type = 'warning'; break;
								case 2: var type = 'success'; break;
								case 3: var type = 'error'; break;
							}
							$('#save-remote-images-button').data('noty').close();
							var n = noty({
								text: respond.msg,
								type: type,
								layout: 'center',
								timeout: 3000,
								theme: noty_theme
							});
							if (respond.content) tinyMCE.activeEditor.setContent(respond.content);
						},
						error: catch_error
					});
					break;						
			}
		});
	});
});