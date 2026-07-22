/* Falcı Profilleri — Admin JS */
(function ($) {
  'use strict';

  /* ----------------------------------------------------------------
     WordPress Media Library picker for profile photo
  ----------------------------------------------------------------- */
  var mediaFrame;

  $(document).on('click', '#falci-foto-secici', function (e) {
    e.preventDefault();

    if (mediaFrame) {
      mediaFrame.open();
      return;
    }

    mediaFrame = wp.media({
      title:    'Profil Fotoğrafı Seç',
      button:   { text: 'Fotoğrafı Kullan' },
      multiple: false,
      library:  { type: 'image' }
    });

    mediaFrame.on('select', function () {
      var attachment = mediaFrame.state().get('selection').first().toJSON();
      var thumb      = attachment.sizes && attachment.sizes.thumbnail
        ? attachment.sizes.thumbnail.url
        : attachment.url;

      $('#_falci_foto_id').val(attachment.id);
      $('#falci-foto-preview-img')
        .attr('src', thumb)
        .css({ display: 'block', width: '80px', height: '80px', 'object-fit': 'cover', 'border-radius': '50%' });
      $('#falci-foto-secici').text('Fotoğrafı Değiştir');

      if (!$('#falci-foto-kaldir').length) {
        $('<button type="button" class="button" id="falci-foto-kaldir" style="margin-left:6px;">Fotoğrafı Kaldır</button>')
          .insertAfter('#falci-foto-secici');
      }
    });

    mediaFrame.open();
  });

  $(document).on('click', '#falci-foto-kaldir', function (e) {
    e.preventDefault();
    $('#_falci_foto_id').val('');
    $('#falci-foto-preview-img').removeAttr('src').hide();
    $('#falci-foto-secici').text('Fotoğraf Seç');
    $(this).remove();
  });

}(jQuery));
