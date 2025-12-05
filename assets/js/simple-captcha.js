(function ($) {
function refreshCaptcha(wrapper) {
if (!SCaptcha || !SCaptcha.ajaxUrl) {
return;
}

$.post(
SCaptcha.ajaxUrl,
{
action: 'scaptcha_refresh',
nonce: SCaptcha.nonce,
},
function (response) {
if (!response || !response.success) {
return;
}

var data = response.data;
wrapper.find('.scaptcha-image img').attr('src', data.image_url);
wrapper.attr('data-token', data.token);
wrapper.find('input[name="scaptcha_token"]').val(data.token);
wrapper.find('input[name="scaptcha_input"]').val('');
}
);
}

$(document).on('click', '.scaptcha-refresh', function () {
var wrapper = $(this).closest('.scaptcha-wrapper');
refreshCaptcha(wrapper);
});
})(jQuery);
