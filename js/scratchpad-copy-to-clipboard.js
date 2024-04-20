// A simple Drupal behavior that applies to the .copy-to-clipboard class. Hardcoded for the scratchpad form in it's current state.
(function ($, Drupal) {
  Drupal.behaviors.copyToClipboard = {
    attach: function (context, settings) {
      $(".copy-to-clipboard").on("click", function () {
        // Prevent submit
        event.preventDefault();
        var text = $(".workarea-textarea").val();
        // If empty just bail
        if (!text) {
          return;
        }
        // Get the text field
        if (navigator.clipboard) {
          // Clipboard API is available
          navigator.clipboard
            .writeText(text)
            .then(() => {
              console.log("Text copied to clipboard!");
            })
            .catch((err) => {
              console.error("Failed to copy text: ", err);
            });
        } else {
          // Obnoxious alert
          alert("Clipboard API not available.");

          console.log("Clipboard API not available.");
        }
      });
    },
  };
})(jQuery, Drupal);
