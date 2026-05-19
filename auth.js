/* Auth pages — client-side polish: live validation, password strength,
 * show/hide, OTP digit hop & paste, social-button toast.
 */
(function () {
    'use strict';

    // ----- Toast -----
    const toast = document.getElementById('auth-toast');
    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('is-shown');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.remove('is-shown'), 1800);
    }
    document.querySelectorAll('[data-coming-soon]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            showToast('Social login is coming soon — use email for now');
        });
    });

    // ----- Password show/hide -----
    document.querySelectorAll('.auth-password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-toggle');
            const inp = document.getElementById(targetId);
            if (!inp) return;
            const isPwd = inp.type === 'password';
            inp.type = isPwd ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) icon.className = isPwd ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    });

    // ----- Live field validation helpers -----
    function setError(field, message) {
        if (!field) return;
        const wrap = field.closest('.auth-field');
        if (!wrap) return;
        wrap.classList.add('has-error');
        wrap.classList.remove('is-valid');
        const msg = wrap.querySelector('.auth-error-msg');
        if (msg && message) msg.textContent = message;
    }
    function clearError(field, asValid) {
        if (!field) return;
        const wrap = field.closest('.auth-field');
        if (!wrap) return;
        wrap.classList.remove('has-error');
        if (asValid) wrap.classList.add('is-valid'); else wrap.classList.remove('is-valid');
    }

    const isEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);

    // ----- Login form -----
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        const email = loginForm.querySelector('#login-email');
        const pwd   = loginForm.querySelector('#login-password');

        email.addEventListener('input', () => {
            if (!email.value) clearError(email);
            else if (!isEmail(email.value)) setError(email, 'Enter a valid email address.');
            else clearError(email, true);
        });
        pwd.addEventListener('input', () => {
            if (!pwd.value) clearError(pwd);
            else if (pwd.value.length < 6) setError(pwd, 'Password must be at least 6 characters.');
            else clearError(pwd, true);
        });

        loginForm.addEventListener('submit', (e) => {
            let ok = true;
            if (!isEmail(email.value)) { setError(email, 'Enter a valid email address.'); ok = false; }
            if (!pwd.value || pwd.value.length < 6) { setError(pwd, 'Password must be at least 6 characters.'); ok = false; }
            if (!ok) e.preventDefault();
        });
    }

    // ----- Register form -----
    const regForm = document.getElementById('register-form');
    if (regForm) {
        const name    = regForm.querySelector('#reg-name');
        const email   = regForm.querySelector('#reg-email');
        const pwd     = regForm.querySelector('#reg-password');
        const confirm = regForm.querySelector('#reg-confirm');
        const strength = document.getElementById('strength');
        const rules    = document.getElementById('pwd-rules');

        function evaluate(v) {
            return {
                len:   v.length >= 8,
                upper: /[A-Z]/.test(v),
                num:   /[0-9]/.test(v),
                sym:   /[^A-Za-z0-9]/.test(v),
            };
        }
        function applyStrength(v) {
            const r = evaluate(v);
            // Update checklist
            if (rules) rules.querySelectorAll('li').forEach(li => {
                const key = li.dataset.rule;
                li.classList.toggle('ok', !!r[key]);
                const ic = li.querySelector('i');
                if (ic) ic.className = r[key] ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle';
            });
            const score = [r.len, r.upper, r.num, r.sym].filter(Boolean).length;
            if (strength) {
                strength.classList.remove('lvl-1','lvl-2','lvl-3','lvl-4');
                if (v.length > 0) strength.classList.add('lvl-' + Math.max(1, score));
            }
            return r;
        }

        name.addEventListener('input', () => {
            if (!name.value.trim()) clearError(name);
            else clearError(name, true);
        });
        email.addEventListener('input', () => {
            if (!email.value) clearError(email);
            else if (!isEmail(email.value)) setError(email, 'Enter a valid email.');
            else clearError(email, true);
        });
        pwd.addEventListener('input', () => {
            const r = applyStrength(pwd.value);
            if (!pwd.value) clearError(pwd);
            else if (!r.len)        setError(pwd, 'At least 8 characters.');
            else if (!r.upper)      setError(pwd, 'Add at least 1 uppercase letter.');
            else if (!r.num)        setError(pwd, 'Add at least 1 number.');
            else                    clearError(pwd, true);

            // Re-check confirm
            if (confirm.value) confirm.dispatchEvent(new Event('input'));
        });
        confirm.addEventListener('input', () => {
            if (!confirm.value) clearError(confirm);
            else if (confirm.value !== pwd.value) setError(confirm, "Passwords don't match.");
            else clearError(confirm, true);
        });

        regForm.addEventListener('submit', (e) => {
            let ok = true;
            if (!name.value.trim())                { setError(name, 'Please enter your name.'); ok = false; }
            if (!isEmail(email.value))             { setError(email, 'Enter a valid email.');  ok = false; }
            const r = evaluate(pwd.value);
            if (!r.len || !r.upper || !r.num)      { setError(pwd, 'Min 8 chars, 1 uppercase, 1 number.'); ok = false; }
            if (confirm.value !== pwd.value)       { setError(confirm, "Passwords don't match."); ok = false; }
            if (!ok) e.preventDefault();
        });
    }

    // ----- OTP digit hop, paste, backspace -----
    const otpWrap = document.getElementById('otp-wrap');
    if (otpWrap) {
        const inputs = Array.from(otpWrap.querySelectorAll('input'));
        inputs[0]?.focus();
        inputs.forEach((inp, i) => {
            inp.addEventListener('input', (e) => {
                inp.value = inp.value.replace(/\D/g, '').slice(-1);
                if (inp.value && i < inputs.length - 1) inputs[i + 1].focus();
            });
            inp.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !inp.value && i > 0) {
                    inputs[i - 1].focus();
                    inputs[i - 1].value = '';
                    e.preventDefault();
                } else if (e.key === 'ArrowLeft' && i > 0) {
                    inputs[i - 1].focus(); e.preventDefault();
                } else if (e.key === 'ArrowRight' && i < inputs.length - 1) {
                    inputs[i + 1].focus(); e.preventDefault();
                }
            });
            inp.addEventListener('paste', (e) => {
                const data = (e.clipboardData || window.clipboardData).getData('text');
                const digits = (data || '').replace(/\D/g, '').slice(0, 6);
                if (!digits) return;
                e.preventDefault();
                for (let j = 0; j < digits.length && (i + j) < inputs.length; j++) {
                    inputs[i + j].value = digits[j];
                }
                const next = Math.min(i + digits.length, inputs.length - 1);
                inputs[next].focus();
            });
        });

        const resendBtn = document.getElementById('otp-resend');
        if (resendBtn) {
            resendBtn.addEventListener('click', () => {
                showToast('Code resent — demo build still accepts 123456');
            });
        }
    }
})();
