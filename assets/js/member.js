/* Member Login — progressive enhancement only.
   Without JS the form still works (plain POST); reCAPTCHA is simply skipped. */
(function () {
  'use strict';

  var cfg = window.sjiocMember || {};
  var form = document.querySelector('.sjioc-member-form');
  if (!form) return;

  // Remember which method button was clicked (lost on programmatic submit).
  var chosen = null;
  form.querySelectorAll('button[name="method"]').forEach(function (b) {
    b.addEventListener('click', function () { chosen = b.value; });
  });

  form.addEventListener('submit', function (e) {
    if (form.dataset.submitting) { e.preventDefault(); return; }

    if (!cfg.recaptchaKey || typeof grecaptcha === 'undefined') {
      form.dataset.submitting = '1';
      return; // let the native submit proceed
    }

    e.preventDefault();
    form.dataset.submitting = '1';

    if (chosen && !form.querySelector('input[name="method"]')) {
      var h = document.createElement('input');
      h.type = 'hidden';
      h.name = 'method';
      h.value = chosen;
      form.appendChild(h);
    }

    grecaptcha.ready(function () {
      grecaptcha.execute(cfg.recaptchaKey, { action: 'member_auth' }).then(function (token) {
        var field = form.querySelector('input[name="recaptcha_token"]');
        if (field) field.value = token;
        form.submit();
      }).catch(function () {
        form.submit(); // reCAPTCHA verify fails open server-side
      });
    });
  });
})();
