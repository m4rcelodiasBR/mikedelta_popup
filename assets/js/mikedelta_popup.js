/**
 * @file
 * Comportamento de JavaScript para o popup do módulo MikeDelta Popup.
 *
 * Este arquivo lida com a lógica de front-end para exibir, animar e fechar o
 * pop-up com base nas configurações passadas pelo PHP via drupalSettings.
 * Ele garante que o pop-up apareça apenas uma vez por sessão de aba.
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Comportamento do Drupal para inicializar o MikeDelta Popup.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   * A função que anexa e inicializa a lógica do pop-up.
   */
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

        /**
         * Lida com o fechamento e remoção do pop-up com uma animação de fade-out.
         */
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