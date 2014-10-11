jQuery(function($) {
	$(window).on('load', function() {
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
						closeWith: ['button']
					}) );
					$.ajax({
						type: "POST",
						url: ajaxurl,
						data: {
							action: 'save_remote_images',
							post_id: QASI.post_id,
							content: encodeURI(encodeURI($('#content').val()))
						},
						success: function(respond) {
							$('#save-remote-images-button').data('noty').close();
							var n = noty({
								text: QASI.succesed_save_remote_images,	
								type: 'success',
								layout: 'center',
								timeout: 3000
							});
							if (respond) $('#content').val(respond);
						}
					});
					break;
				case 'virtual':
					$('#save-remote-images-button').data('noty', noty({
						text: QASI.in_process,	
						type: 'notification',
						layout: 'center',
						modal: true,
						closeWith: ['button']
					}) );
					$.ajax({
						type: "POST",
						url: ajaxurl,
						data: {
							action: 'save_remote_images',
							post_id: QASI.post_id,
							content: encodeURI(encodeURI(tinyMCE.activeEditor.getContent()))
						},
						success: function(respond) {
							$('#save-remote-images-button').data('noty').close();
							var n = noty({
								text: QASI.succesed_save_remote_images,	
								type: 'success',
								layout: 'center',
								timeout: 3000
							});
							if (respond) tinyMCE.activeEditor.setContent(respond);
						}
					});
					break;						
			}
		});
	});
});