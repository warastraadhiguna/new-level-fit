<script>
    (function () {
        if (window.__deleteConfirmationInstalled) {
            return;
        }
        window.__deleteConfirmationInstalled = true;

        function isDeleteForm(form) {
            if (!form || form.tagName !== 'FORM') {
                return false;
            }

            var methodOverride = form.querySelector('input[name="_method"]');
            return methodOverride && String(methodOverride.value).toUpperCase() === 'DELETE';
        }

        function cleanText(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function getDeleteDetails(form, submitter) {
            var explicitDetail = cleanText(
                form.getAttribute('data-delete-detail') ||
                (submitter && submitter.getAttribute('data-delete-detail'))
            );
            if (explicitDetail) {
                return explicitDetail;
            }

            var checkedItems = form.querySelectorAll('input[type="checkbox"]:checked');
            if (checkedItems.length > 1) {
                return checkedItems.length + ' data terpilih';
            }

            var row = form.closest('tr');
            if (row) {
                var details = [];
                Array.prototype.forEach.call(row.cells || [], function (cell) {
                    if (cell.contains(form) || details.length >= 3) {
                        return;
                    }

                    var copy = cell.cloneNode(true);
                    Array.prototype.forEach.call(copy.querySelectorAll('button, form, script, style'), function (element) {
                        element.remove();
                    });
                    var text = cleanText(copy.textContent);
                    if (text && !/^\d+$/.test(text) && text.toLowerCase() !== 'action') {
                        details.push(text.substring(0, 90));
                    }
                });

                if (details.length) {
                    return details.join(' | ');
                }
            }

            var container = form.closest('.card, .modal-content, .accordion-item');
            if (container) {
                var heading = container.querySelector('h1, h2, h3, h4, h5, h6, .heading, .modal-title');
                var headingText = cleanText(heading && heading.textContent);
                if (headingText) {
                    return headingText.substring(0, 180);
                }
            }

            var action = String(form.getAttribute('action') || '').split('?')[0].replace(/\/$/, '');
            var segments = action.split('/').filter(Boolean);
            return segments.length ? 'ID/tujuan: ' + segments.slice(-2).join(' / ') : 'Data pada halaman ini';
        }

        function submitDeleteForm(form) {
            form.dataset.deleteConfirmed = '1';
            HTMLFormElement.prototype.submit.call(form);
        }

        function askForDeleteConfirmation(form, submitter) {
            if (form.dataset.deleteConfirmationPending === '1') {
                return;
            }
            form.dataset.deleteConfirmationPending = '1';

            var details = getDeleteDetails(form, submitter);
            var buttonText = cleanText(submitter && submitter.textContent).toLowerCase();
            var isPermanent = buttonText.indexOf('force') !== -1 || buttonText.indexOf('permanent') !== -1;
            var title = isPermanent
                ? 'Apakah Anda yakin ingin menghapus permanen?'
                : 'Apakah Anda yakin ingin menghapus data ini?';
            var message = 'Data yang akan dihapus:\n' + details +
                (isPermanent ? '\n\nData ini tidak dapat dipulihkan.' : '');

            if (typeof window.swal === 'function') {
                window.swal({
                    title: title,
                    text: message,
                    icon: 'warning',
                    buttons: {
                        cancel: 'Batal',
                        confirm: {
                            text: isPermanent ? 'Ya, hapus permanen' : 'Ya, hapus',
                            value: true,
                            visible: true,
                            closeModal: true
                        }
                    },
                    dangerMode: true
                }).then(function (confirmed) {
                    if (confirmed) {
                        submitDeleteForm(form);
                    } else {
                        delete form.dataset.deleteConfirmationPending;
                    }
                });
                return;
            }

            if (window.confirm(title + '\n\n' + message)) {
                submitDeleteForm(form);
            } else {
                delete form.dataset.deleteConfirmationPending;
            }
        }

        document.addEventListener('click', function (event) {
            var submitter = event.target.closest('button, input[type="submit"]');
            if (!submitter) {
                return;
            }

            var submitterType = String(submitter.getAttribute('type') || 'submit').toLowerCase();
            var form = submitter.form || submitter.closest('form');
            if (submitterType !== 'submit' || !isDeleteForm(form) || form.dataset.deleteConfirmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            askForDeleteConfirmation(form, submitter);
        }, true);

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!isDeleteForm(form) || form.dataset.deleteConfirmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            askForDeleteConfirmation(form, event.submitter || document.activeElement);
        }, true);
    })();
</script>
