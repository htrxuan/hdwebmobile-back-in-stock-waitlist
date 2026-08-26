(function ($) {
	'use strict';

	if (typeof hdbisParams === 'undefined') {
		return;
	}

	function submitForm($form) {
		var $message = $form.find('.hdbis-notify-form__message');
		var $submit = $form.find('.hdbis-notify-form__submit');

		$message.text('');
		$submit.prop('disabled', true);

		$.post(hdbisParams.ajax_url, {
			action: 'hdbis_subscribe',
			nonce: hdbisParams.nonce,
			product_id: $form.find('input[name="product_id"]').val(),
			variation_id: $form.find('input[name="variation_id"]').val(),
			email: $form.find('input[name="email"]').val(),
			name: $form.find('input[name="name"]').val(),
		}).done(function (response) {
			if (response && response.success) {
				$message.text(hdbisParams.i18n.success);
				$form.find('input, button').prop('disabled', true);
			} else {
				var message = (response && response.data && response.data.message) ? response.data.message : hdbisParams.i18n.error;
				$message.text(message);
				$submit.prop('disabled', false);
			}
		}).fail(function () {
			$message.text(hdbisParams.i18n.error);
			$submit.prop('disabled', false);
		});
	}

	$(document).on('submit', '.hdbis-notify-form', function (e) {
		e.preventDefault();
		submitForm($(this));
	});

	// Reuse WooCommerce's own variation selection state -- no custom logic here.
	$('.variations_form').on('found_variation', function (e, variation) {
		var $wrap = $(this).find('.hdbis-notify-form-wrap');
		if (!$wrap.length) {
			return;
		}

		if (!variation.is_in_stock) {
			$wrap.find('input[name="variation_id"]').val(variation.variation_id);
			$wrap.show();
		} else {
			$wrap.hide();
		}
	});

	$('.variations_form').on('reset_data', function () {
		$(this).find('.hdbis-notify-form-wrap').hide();
	});

})(jQuery);
