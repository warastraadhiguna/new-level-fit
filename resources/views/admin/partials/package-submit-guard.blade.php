<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.js-idempotent-package-form');

    function resetForm(form) {
        form.dataset.submitting = 'false';

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(button) {
            button.disabled = false;

            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }

            if (button.dataset.originalValue) {
                button.value = button.dataset.originalValue;
            }
        });
    }

    forms.forEach(function(form) {
        resetForm(form);

        form.addEventListener('submit', function(event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            form.dataset.submitting = 'true';

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(button) {
                button.disabled = true;

                if (button.tagName === 'BUTTON') {
                    button.dataset.originalHtml = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...';
                } else {
                    button.dataset.originalValue = button.value;
                    button.value = 'Menyimpan...';
                }
            });
        });
    });

    window.addEventListener('pageshow', function() {
        forms.forEach(resetForm);
    });
});
</script>
