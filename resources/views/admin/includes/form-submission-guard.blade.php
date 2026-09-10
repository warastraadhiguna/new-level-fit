<script>
    (function () {
        if (window.__formSubmissionGuardInstalled) {
            return;
        }
        window.__formSubmissionGuardInstalled = true;

        function isProtectedForm(form) {
            if (!form || form.tagName !== 'FORM' || form.hasAttribute('data-allow-repeat-submit')) {
                return false;
            }

            var htmlMethod = String(form.getAttribute('method') || 'GET').toUpperCase();
            if (htmlMethod !== 'POST') {
                return false;
            }

            var methodOverride = form.querySelector('input[name="_method"]');
            var effectiveMethod = methodOverride
                ? String(methodOverride.value).toUpperCase()
                : htmlMethod;

            return effectiveMethod === 'POST' || effectiveMethod === 'PUT' || effectiveMethod === 'PATCH';
        }

        function createToken() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
                var random = Math.random() * 16 | 0;
                var value = character === 'x' ? random : (random & 0x3 | 0x8);
                return value.toString(16);
            });
        }

        function ensureSubmissionToken(form) {
            var input = form.querySelector('input[name="_form_submission_token"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_form_submission_token';
                form.appendChild(input);
            }
            if (!input.value) {
                input.value = createToken();
            }
        }

        function lockSubmitButtons(form) {
            Array.prototype.forEach.call(
                form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])'),
                function (button) {
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');
                    button.dataset.submissionLocked = '1';

                    if (button.tagName === 'BUTTON' && !button.querySelector('.spinner-border')) {
                        button.dataset.originalSubmitText = button.innerHTML;
                        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
                    }
                }
            );
        }

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!isProtectedForm(form) || event.defaultPrevented) {
                return;
            }

            if (form.dataset.submissionInProgress === '1') {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            ensureSubmissionToken(form);
            form.dataset.submissionInProgress = '1';

            window.setTimeout(function () {
                lockSubmitButtons(form);
            }, 0);
        }, false);

        window.addEventListener('pageshow', function () {
            Array.prototype.forEach.call(document.querySelectorAll('form[data-submission-in-progress="1"]'), function (form) {
                delete form.dataset.submissionInProgress;
            });

            Array.prototype.forEach.call(document.querySelectorAll('[data-submission-locked="1"]'), function (button) {
                button.disabled = false;
                button.removeAttribute('aria-disabled');
                delete button.dataset.submissionLocked;

                if (button.dataset.originalSubmitText) {
                    button.innerHTML = button.dataset.originalSubmitText;
                    delete button.dataset.originalSubmitText;
                }
            });
        });
    })();
</script>
