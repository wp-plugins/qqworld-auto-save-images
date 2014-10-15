jQuery(function($) {
	$(window).on('load', function() {
		var noty_theme = typeof qqworld_ajax == 'object' ? 'qqworldTheme' : 'defaultTheme';
		$('.mce-i-save_remote_images').closest('.mce-widget').hide();
		$(document).on('click', '#save-remote-images-button', function() {
			var mode = 'text';
			if (tinyMCE.activeEditor) {
				var id = tinyMCE.activeEditor.id;
				mode = $('#'+id).is(':visible') ? 'text' : 'virtual';
			}
			switch (mode) {
				case 'text':
					$('#save-remote-images-button').data('noty', noty({
						text: QASI.in_process,	
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
							if (respond.content) $('#content').val(respond.content);
						}
					});
					break;
				case 'virtual':
					$('#save-remote-images-button').data('noty', noty({
						text: QASI.in_process,	
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
						}
					});
					break;						
			}
		});
	});
});