/**
 * GEO AI Woo — Admin JavaScript
 *
 * @package GeoAiWoo
 */

(function ($) {
	'use strict';

	// Regenerate llms.txt button
	$('#geo-ai-woo-regenerate').on('click', function () {
		var $btn = $(this);
		var $status = $('#geo-ai-woo-regenerate-status');

		$btn.prop('disabled', true);
		$status
			.removeClass('success error')
			.text(geo_ai_woo_admin.regenerating);

		$.post(
			ajaxurl,
			{
				action: 'geo_ai_woo_regenerate',
				nonce: geo_ai_woo_admin.nonce,
			},
			function (response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$status.addClass('success').text(geo_ai_woo_admin.done);
				} else {
					$status.addClass('error').text(geo_ai_woo_admin.error);
				}
				setTimeout(function () {
					$status.removeClass('success error').text('');
				}, 3000);
			}
		).fail(function () {
			$btn.prop('disabled', false);
			$status.addClass('error').text(geo_ai_woo_admin.error);
		});
	});

	// AI Description character counter (meta box)
	$('#geo_ai_woo_description').on('input', function () {
		var $textarea = $(this);
		var maxLength = 200;
		var currentLength = $textarea.val().length;

		var $counter = $textarea.next('.geo-ai-woo-char-count');
		if (!$counter.length) {
			$counter = $(
				'<span class="description geo-ai-woo-char-count"></span>'
			);
			$textarea.after($counter);
		}

		$counter.text(currentLength + '/' + maxLength);

		if (currentLength > maxLength) {
			$counter.css('color', '#dc3232');
		} else {
			$counter.css('color', '');
		}
	});
})(jQuery);
