(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.mikeDeltaPopup = {
    attach: function (context, settings) {
      const elementsToProcess = once('md-popup', 'body', context);
      elementsToProcess.forEach(function (element) {
        
        if (!drupalSettings.mikedelta_popup) { return; }
        if (sessionStorage.getItem('mdPopupShown')) { return; }

        const popupSettings = drupalSettings.mikedelta_popup;
        let contentHtml = '';

        if (popupSettings.type === 'image' && popupSettings.imageUrl) {
          contentHtml = `<img src="${popupSettings.imageUrl}" alt="Popup Image">`;
        } 
        else if (popupSettings.type === 'text' && popupSettings.text.trim() !== '') {
          contentHtml = `<div class="popup-text-content">${popupSettings.text}</div>`;
        }

        if (!contentHtml) { return; }
        
        let linkWrapperStart = '';
        let linkWrapperEnd = '';
        if (popupSettings.linkUrl && popupSettings.linkUrl !== '#') {
          linkWrapperStart = `<a href="${popupSettings.linkUrl}" target="${popupSettings.linkTarget}">`;
          linkWrapperEnd = `</a>`;
        }
        
        const popupHtml = `
          <div id="md-popup-overlay">
            <div id="md-popup-glass-pane" class="popup-type-${popupSettings.type}">
              <div id="md-popup-content">
                ${linkWrapperStart}
                ${contentHtml}
                ${linkWrapperEnd}
              </div>
              <div id="md-popup-close">&times;</div>
            </div>
          </div>
        `;
        
        $(element).append(popupHtml);
        
        const $overlay = $('#md-popup-overlay');

        setTimeout(function() { $overlay.addClass('is-visible'); }, 50);
        function closeModal() {
          $overlay.removeClass('is-visible');
          setTimeout(function() { $overlay.remove(); }, 300);
        }
        $overlay.find('#md-popup-close').on('click', closeModal);
        $overlay.on('click', function(e) { if (e.target === this) { closeModal(); } });

        sessionStorage.setItem('mdPopupShown', 'true');
      });
    }
  };
})(jQuery, Drupal, drupalSettings, once);