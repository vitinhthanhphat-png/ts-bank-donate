/* jshint esversion: 6 */
(function () {
    'use strict';

    // ─── Toast ────────────────────────────────────────────────────────────────
    let toastTimer;
    function showToast(msg) {
        let toast = document.getElementById('tsbd-global-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'tsbd-global-toast';
            toast.className = 'tsbd-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 2500);
    }

    // ─── Format VND ──────────────────────────────────────────────────────────
    function fmtVND(val) {
        const n = parseInt(val, 10);
        if (isNaN(n) || n <= 0) return '';
        return n.toLocaleString('vi-VN') + 'đ';
    }

    // ─── Init all boxes ───────────────────────────────────────────────────────
    document.querySelectorAll('.tsbd-box').forEach(initBox);

    function initBox(box) {

        // ── Tabs ──────────────────────────────────────────────────────────────
        const tabs   = box.querySelectorAll('.tsbd-tab');
        const panels = box.querySelectorAll('.tsbd-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
                panels.forEach(p => p.classList.remove('is-active'));

                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');

                const target = document.getElementById(tab.dataset.target);
                if (target) {
                    target.classList.add('is-active');
                }
            });
        });

        // ── Amount suggestion buttons ─────────────────────────────────────────
        box.querySelectorAll('.tsbd-panel').forEach(panel => {

            const amountBtns = panel.querySelectorAll('.tsbd-amount-btn');
            const amountInput = panel.querySelector('.tsbd-amount-input');

            amountBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    amountBtns.forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    if (amountInput) {
                        amountInput.value = btn.dataset.amount;
                    }
                });
            });

            // Deselect amount buttons if user types manually
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    amountBtns.forEach(b => b.classList.remove('is-active'));
                });
            }

            // ── Copy button ───────────────────────────────────────────────────
            const copyBtn = panel.querySelector('.tsbd-copy-btn');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const accountNo   = panel.dataset.accountNo   || '';
                    const accountName = panel.dataset.accountName || '';
                    const bankName    = panel.dataset.bankName    || '';
                    const amount      = amountInput ? amountInput.value.trim() : '';
                    const noteInput   = panel.querySelector('.tsbd-note-input');
                    const note        = noteInput ? noteInput.value.trim() : (panel.dataset.defaultNote || '');

                    const parts = [bankName, accountNo, accountName];
                    if (amount) parts.push(fmtVND(amount));
                    if (note)   parts.push(note);

                    const text = parts.filter(Boolean).join(' | ');

                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text).then(() => {
                            copyBtn.classList.add('is-copied');
                            showToast('✅ Đã copy thông tin chuyển khoản!');
                            setTimeout(() => copyBtn.classList.remove('is-copied'), 2000);
                        });
                    } else {
                        // Fallback for older browsers
                        const el = document.createElement('textarea');
                        el.value = text;
                        el.style.position = 'fixed';
                        el.style.opacity = '0';
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        showToast('✅ Đã copy thông tin chuyển khoản!');
                    }
                });
            }
        });
    }

}());
