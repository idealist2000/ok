/* Falcı Profilleri — Frontend JS */
(function ($) {
  'use strict';

  /* ----------------------------------------------------------------
     Photo preview on file input change
  ----------------------------------------------------------------- */
  $(document).on('change', '#ff_profil_foto', function () {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#ff-foto-preview-img').attr('src', e.target.result);
      $('#ff-foto-preview').show();
    };
    reader.readAsDataURL(file);
  });

  /* ----------------------------------------------------------------
     Biography character counter
  ----------------------------------------------------------------- */
  function updateBiyoCounter() {
    var len     = $('#ff_biyografi').val().length;
    var counter = $('#ff-biyo-count');
    counter.text(len);
    var parent = counter.closest('.falci-char-counter');
    parent.removeClass('ok warn');
    if (len >= 300) {
      parent.addClass('ok');
    } else if (len >= 200) {
      parent.addClass('warn');
    }
  }
  $(document).on('input', '#ff_biyografi', updateBiyoCounter);

  /* ----------------------------------------------------------------
     Form AJAX submission
  ----------------------------------------------------------------- */
  $(document).on('submit', '#falci-basvuru-form', function (e) {
    e.preventDefault();

    var $form   = $(this);
    var $btn    = $('#falci-submit-btn');
    var $msgBox = $('#falci-form-mesaj');

    // Basic client-side check
    if ($('#ff_biyografi').val().length < 300) {
      showMessage($msgBox, 'Biyografi en az 300 karakter olmalıdır.', 'hata');
      return;
    }

    var checked = $('input[name="uzmanliklar[]"]:checked').length;
    if (checked === 0) {
      showMessage($msgBox, 'En az bir uzmanlık alanı seçmelisiniz.', 'hata');
      return;
    }

    // UI: loading state
    $btn.prop('disabled', true);
    $btn.find('.btn-text').hide();
    $btn.find('.btn-spinner').show();
    $msgBox.hide();

    var formData = new FormData($form[0]);
    formData.append('action', 'falci_basvuru_gonder');
    formData.append('nonce', falciData.nonce);

    $.ajax({
      url:         falciData.ajaxurl,
      type:        'POST',
      data:        formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.success) {
          showMessage($msgBox, res.data.message, 'basari');
          $form[0].reset();
          $('#ff-foto-preview').hide();
          updateBiyoCounter();
          // Scroll to message
          $('html, body').animate({ scrollTop: $msgBox.offset().top - 100 }, 400);
        } else {
          showMessage($msgBox, res.data.message, 'hata');
        }
      },
      error: function () {
        showMessage($msgBox, 'Sunucu hatası oluştu. Lütfen tekrar deneyin.', 'hata');
      },
      complete: function () {
        $btn.prop('disabled', false);
        $btn.find('.btn-text').show();
        $btn.find('.btn-spinner').hide();
      }
    });
  });

  /* ----------------------------------------------------------------
     Filter bar — AJAX list reload (shortcode pages)
  ----------------------------------------------------------------- */
  $(document).on('click', '.falci-filtre-btn', function (e) {
    // Only intercept if the list wrapper has a data-ajax="true" attribute,
    // otherwise fall through to normal href navigation.
    if (!$(this).closest('.falci-liste-wrapper').data('ajax')) return;
    e.preventDefault();
    var $btn     = $(this);
    var uzmanlik = $btn.data('uzmanlik');
    $btn.closest('.falci-filtre-buttons').find('.falci-filtre-btn').removeClass('active');
    $btn.addClass('active');

    var $grid = $btn.closest('.falci-liste-wrapper').find('.falci-grid');
    $grid.css({ opacity: 0.4, 'pointer-events': 'none' });

    $.post(falciData.ajaxurl, {
      action:   'falci_listesi_ajax',
      nonce:    falciData.nonce,
      uzmanlik: uzmanlik
    }, function (res) {
      if (res.success) {
        $grid.replaceWith(res.data.html);
      }
    }).always(function () {
      $grid.css({ opacity: 1, 'pointer-events': '' });
    });
  });

  /* ----------------------------------------------------------------
     Helpers
  ----------------------------------------------------------------- */
  function showMessage($el, msg, type) {
    $el
      .removeClass('falci-mesaj-basari falci-mesaj-hata')
      .addClass('falci-mesaj falci-mesaj-' + type)
      .html(msg)
      .show();
  }

}(jQuery));
