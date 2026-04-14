/**
 * @file
 * Comportamento de JavaScript para o popup do módulo MikeDelta Popup.
 */
(function ($, Drupal, drupalSettings, once) {
  "use strict";

  Drupal.behaviors.mikeDeltaPopup = {
    attach: function (context, settings) {
      const elementsToProcess = once("md-popup", "body", context);
      elementsToProcess.forEach(function (element) {
        if (!drupalSettings.mikedelta_popup) {
          return;
        }
        if (sessionStorage.getItem("mdPopupShown")) {
          return;
        }

        const popupSettings = drupalSettings.mikedelta_popup;
        let contentHtml = "";
        let linkWrapperStart = "";
        let linkWrapperEnd = "";

        if (
          popupSettings.type === "image" &&
          popupSettings.gallery &&
          popupSettings.gallery.length > 0
        ) {
          let lastIndex = parseInt(
            localStorage.getItem("mdPopupLastIndex"),
            10,
          );
          if (isNaN(lastIndex)) {
            lastIndex = -1;
          }

          const nextIndex = (lastIndex + 1) % popupSettings.gallery.length;
          localStorage.setItem("mdPopupLastIndex", nextIndex);

          const currentImage = popupSettings.gallery[nextIndex];

          // Higienização (Anti-XSS) para saídas inseridas dinamicamente
          const safeImageUrl = Drupal.checkPlain(currentImage.imageUrl);
          contentHtml = `<img src="${safeImageUrl}" alt="Popup Image">`;

          if (currentImage.linkUrl && currentImage.linkUrl !== "#") {
            const safeLinkUrl = encodeURI(currentImage.linkUrl);
            const safeTarget = Drupal.checkPlain(currentImage.linkTarget);
            linkWrapperStart = `<a href="${safeLinkUrl}" target="${safeTarget}">`;
            linkWrapperEnd = `</a>`;
          }
        } else if (
          popupSettings.type === "text" &&
          popupSettings.text &&
          popupSettings.text.trim() !== ""
        ) {
          contentHtml = `<div class="popup-text-content">${popupSettings.text}</div>`;
        }

        if (!contentHtml) {
          return;
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

        const $overlay = $("#md-popup-overlay");

        setTimeout(function () {
          $overlay.addClass("is-visible");
        }, 50);

        function closeModal() {
          $overlay.removeClass("is-visible");
          setTimeout(function () {
            $overlay.remove();
          }, 300);
        }
        $overlay.find("#md-popup-close").on("click", closeModal);
        $overlay.on("click", function (e) {
          if (e.target === this) {
            closeModal();
          }
        });

        sessionStorage.setItem("mdPopupShown", "true");
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);
