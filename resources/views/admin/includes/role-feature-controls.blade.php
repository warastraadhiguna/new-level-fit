@if(Auth::user() && !Auth::user()->isOwner())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rules = @json(app(\App\Support\RoleFeatureAccess::class)->deniedControls(Auth::user()));
        const basePath = new URL(@json(url('/'))).pathname.replace(/\/$/, '');
        const denied = rules.map(function (rule) { return {method: rule.method, path: new RegExp(rule.path)}; });

        function blocked(href, method) {
            if (!href || /^(#|javascript:)/i.test(href)) return false;
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            const path = url.pathname.slice(basePath.length) || '/';
            return denied.some(function (rule) { return rule.method === method && rule.path.test(path); });
        }

        function updateControls() {
            document.querySelectorAll('a[href], form, [data-role-route]').forEach(function (element) {
                const form = element.tagName === 'FORM';
                const override = form && element.querySelector('input[name="_method"]');
                const method = form ? (override ? override.value : element.method).toUpperCase() : (element.dataset.roleMethod || 'GET');
                const href = element.dataset.roleRoute || (form ? element.action : element.getAttribute('href'));
                if (blocked(href, method)) {
                    element.hidden = true;
                    element.style.setProperty('display', 'none', 'important');
                    element.dataset.roleForbidden = '1';
                }
            });
            document.querySelectorAll('[data-bs-target], [data-bs-toggle="modal"][href]').forEach(function (trigger) {
                const selector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
                if (!selector || selector.charAt(0) !== '#') return;
                const modal = document.getElementById(selector.slice(1));
                const forms = modal ? Array.from(modal.querySelectorAll('form')) : [];
                if (forms.length && forms.every(function (form) { return form.dataset.roleForbidden === '1'; })) {
                    trigger.hidden = true;
                    trigger.style.setProperty('display', 'none', 'important');
                }
            });
        }

        updateControls();
        let scheduled = false;
        new MutationObserver(function () {
            if (scheduled) return;
            scheduled = true;
            requestAnimationFrame(function () { scheduled = false; updateControls(); });
        }).observe(document.body, {childList: true, subtree: true});
    });
</script>
@endif
