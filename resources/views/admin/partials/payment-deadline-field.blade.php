<div class="col-xl-6" id="payment_deadline">
    <div class="mb-3">
        <label class="form-label">Payment Deadline (Days)</label>
        <input type="number" name="payment_deadline"
            value="{{ old('payment_deadline', $paymentDeadline ?? 0) }}"
            class="form-control" min="0" step="1">
        <small class="text-muted">
            Enter the number of days allowed for payment. Use 0 for no deadline. Fully paid or strict-branch records are saved as 0.
        </small>
    </div>
</div>

<script>
    (function () {
        const currentScript = document.currentScript;
        const form = currentScript ? currentScript.closest('form') : null;

        if (!form) {
            return;
        }

        const deadlineField = form.querySelector('#payment_deadline');
        const deadlineInput = deadlineField ? deadlineField.querySelector('input[name="payment_deadline"]') : null;
        const firstPaymentInput = form.querySelector('input[name="first_payment"]');
        const packageSelect = form.querySelector('select[name="member_package_id"], select[name="trainer_package_id"]');
        const statusInputs = form.querySelectorAll('input[name="status"]');

        // Form edit tidak memiliki first_payment, jadi deadline tetap selalu terlihat.
        if (!deadlineField || !deadlineInput || !firstPaymentInput || !packageSelect) {
            return;
        }

        function integerValue(value) {
            return parseInt(String(value || '').replace(/\D/g, ''), 10) || 0;
        }

        function isSellStatus() {
            const selectedStatus = form.querySelector('input[name="status"]:checked');
            return !selectedStatus || selectedStatus.value === 'sell';
        }

        function updateDeadlineVisibility() {
            const selectedPackage = packageSelect.options[packageSelect.selectedIndex];
            const hasSelectedPackage = selectedPackage
                && typeof selectedPackage.dataset.packagePrice !== 'undefined';
            const totalPrice = integerValue(selectedPackage ? selectedPackage.dataset.packagePrice : 0)
                + integerValue(selectedPackage ? selectedPackage.dataset.adminPrice : 0);
            const firstPayment = integerValue(firstPaymentInput.value);
            const shouldShow = Boolean(hasSelectedPackage)
                && isSellStatus()
                && firstPayment < totalPrice;

            deadlineField.style.display = shouldShow ? 'block' : 'none';

            if (!shouldShow) {
                deadlineInput.value = 0;
            }
        }

        firstPaymentInput.addEventListener('blur', updateDeadlineVisibility);
        firstPaymentInput.addEventListener('change', updateDeadlineVisibility);
        packageSelect.addEventListener('change', updateDeadlineVisibility);
        statusInputs.forEach(function (input) {
            input.addEventListener('change', updateDeadlineVisibility);
        });

        updateDeadlineVisibility();
    })();
</script>
