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
      },
    );
  }

  function renderYandexCaptcha(wrapper) {
    var siteKey = wrapper.data('sitekey') || (SCaptcha ? SCaptcha.yandexSiteKey : '');
    var container = wrapper.find('.scaptcha-yandex-container').get(0);
    var tokenInput = wrapper.find('input[name="smart-token"]');

    if (!siteKey || !container || tokenInput.length === 0) {
      return;
    }

    var attemptRender = function () {
      if (typeof window.smartCaptcha === 'undefined') {
        setTimeout(attemptRender, 200);
        return;
      }

      window.smartCaptcha.render(container, {
        sitekey: siteKey,
        callback: function (token) {
          tokenInput.val(token);
        },
        'expired-callback': function () {
          tokenInput.val('');
        },
      });
    };

    attemptRender();
  }

  function initYandexCaptchas() {
    $('.scaptcha-wrapper[data-provider="yandex"]').each(function () {
      var wrapper = $(this);

      if (wrapper.data('initialized')) {
        return;
      }

      wrapper.data('initialized', true);

      if (!wrapper.data('sitekey') && SCaptcha && SCaptcha.yandexSiteKey) {
        wrapper.attr('data-sitekey', SCaptcha.yandexSiteKey);
      }

      renderYandexCaptcha(wrapper);
    });
  }

  $(function () {
    initYandexCaptchas();
  });

  $(document).on('click', '.scaptcha-refresh', function () {
    var wrapper = $(this).closest('.scaptcha-wrapper');
    refreshCaptcha(wrapper);
  });
})(jQuery);
