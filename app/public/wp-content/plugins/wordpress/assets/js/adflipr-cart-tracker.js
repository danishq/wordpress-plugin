jQuery(document).ready(function ($) {
  function adflipr_sendEmail(adflipr_email) {
    if (!adflipr_email) return;
    setTimeout(function () {
      $.post(adflipr_vars.ajax_url, {
        action: "adflipr_save_cart",
        nonce: adflipr_vars.nonce,
        security: adflipr_vars.security,
        email: adflipr_email,
      });
    }, 3000);
  }

  function adflipr_isValidEmail() {
    return (
      $(
        ".wc-block-components-text-input.has-error, #billing_email_field.woocommerce-invalid",
      ).length === 0
    );
  }

  function adflipr_checkEmailOnBlur() {
    $(document).on("blur", "#billing_email, input#email", function () {
      let adflipr_emailValue = $(this).val();
      setTimeout(function () {
        if (adflipr_emailValue && adflipr_isValidEmail()) {
          adflipr_sendEmail(adflipr_emailValue);
        }
      }, 100);
    });
  }

  function adflipr_checkAutofilledEmail() {
    setTimeout(function () {
      let adflipr_emailClassic = $("#billing_email").val();
      let adflipr_emailBlock = $("input#email").val();

      if (adflipr_emailClassic && adflipr_isValidEmail()) {
        adflipr_sendEmail(adflipr_emailClassic);
      }

      if (adflipr_emailBlock && adflipr_isValidEmail()) {
        adflipr_sendEmail(adflipr_emailBlock);
      }
    }, 3000); // Wait 3 seconds after page load
  }

  adflipr_checkEmailOnBlur();
  adflipr_checkAutofilledEmail();
});
