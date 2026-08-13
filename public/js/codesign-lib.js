/**
 * CodeSign Lib — UI helpers for the CodeSign
 * @author PatrykMolenda<kontakt@patrykmolenda.pl>
 *
 * Usage:
 *   <script src="codesign-lib.js"></script>
 *   <script>
 *     CS.toast('License created', { type: 'success' });
 *     CS.confirm({ title: 'Delete?', message: '...' }).then(ok => { ... });
 *   </script>
 *
 * Exposes global: window.CS
 */
(function () {
    'use strict';

    /* =========================================================================
     * Private state
     * ======================================================================= */

    let _toastContainer = null;
    let _activeToasts = 0;
    let _confirmStack = 0;

    /* =========================================================================
     * Toast System
     * ======================================================================= */

    const TOAST_ICONS = {
        success: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        error:   '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
        warning: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 2a9 9 0 110 18 9 9 0 010-18z"/></svg>',
        info:    '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };

    const TOAST_COLORS = {
        success: { bg: '#f0fdf4', border: '#86efac', text: '#166534', icon: '#16a34a' },
        error:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: '#dc2626' },
        warning: { bg: '#fffbeb', border: '#fcd34d', text: '#92400e', icon: '#d97706' },
        info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1e40af', icon: '#2563eb' },
    };

    function _ensureToastContainer() {
        if (_toastContainer) return _toastContainer;
        _toastContainer = document.createElement('div');
        _toastContainer.id = 'cs-toast-container';
        _toastContainer.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
            'display:flex', 'flex-direction:column', 'gap:8px',
            'pointer-events:none', 'max-width:380px',
        ].join(';');
        document.body.appendChild(_toastContainer);
        return _toastContainer;
    }

    /**
     * Show a toast notification.
     * @param {string} message
     * @param {object} opts
     * @param {string} opts.type        - success | error | warning | info
     * @param {number} opts.duration    - ms before auto-dismiss (default 3500, 0 = sticky)
     * @param {string} opts.description - secondary text
     * @param {boolean} opts.closable   - show close button (default true)
     */
    function toast(message, opts) {
        opts = opts || {};
        var type = opts.type || 'info';
        var colors = TOAST_COLORS[type] || TOAST_COLORS.info;
        var icon = TOAST_ICONS[type] || TOAST_ICONS.info;

        var container = _ensureToastContainer();
        var el = document.createElement('div');
        el.style.cssText = [
            'display:flex', 'align-items:flex-start', 'gap:10px',
            'padding:12px 14px', 'border-radius:10px',
            'background:' + colors.bg, 'border:1px solid ' + colors.border,
            'color:' + colors.text, 'font-size:14px', 'font-family:Inter,system-ui,sans-serif',
            'box-shadow:0 4px 12px rgba(0,0,0,0.08)',
            'pointer-events:auto', 'min-width:280px',
            'animation:cs-slide-in 0.25s ease-out', 'transition:opacity 0.3s, transform 0.3s',
        ].join(';');

        var iconWrap = document.createElement('div');
        iconWrap.style.cssText = 'width:18px;height:18px;flex-shrink:0;margin-top:1px;color:' + colors.icon;
        iconWrap.innerHTML = icon;
        el.appendChild(iconWrap);

        var content = document.createElement('div');
        content.style.cssText = 'flex:1;line-height:1.4';
        var title = document.createElement('div');
        title.style.cssText = 'font-weight:600';
        title.textContent = message;
        content.appendChild(title);

        if (opts.description) {
            var desc = document.createElement('div');
            desc.style.cssText = 'font-size:12px;opacity:0.75;margin-top:2px';
            desc.textContent = opts.description;
            content.appendChild(desc);
        }
        el.appendChild(content);

        // Close button
        if (opts.closable !== false) {
            var close = document.createElement('button');
            close.style.cssText = 'background:none;border:none;cursor:pointer;padding:0;color:inherit;opacity:0.5;font-size:16px;line-height:1';
            close.innerHTML = '&times;';
            close.onclick = function () { _dismissToast(el); };
            el.appendChild(close);
        }

        // Progress bar
        if (opts.duration !== 0) {
            var bar = document.createElement('div');
            bar.style.cssText = 'position:absolute;bottom:0;left:0;height:2px;border-radius:0 0 0 10px;background:' + colors.icon + ';width:100%;transition:width linear';
            el.style.position = 'relative';
            el.style.overflow = 'hidden';
            el.appendChild(bar);
        }

        container.appendChild(el);
        _activeToasts++;

        if (opts.duration !== 0) {
            var duration = opts.duration || 3500;
            // Animate progress bar
            requestAnimationFrame(function () {
                bar.style.transition = 'width ' + duration + 'ms linear';
                bar.style.width = '0%';
            });
            setTimeout(function () { _dismissToast(el); }, duration);
        }

        return { el: el, dismiss: function () { _dismissToast(el); } };
    }

    function _dismissToast(el) {
        if (!el || !el.parentNode) return;
        el.style.opacity = '0';
        el.style.transform = 'translateX(100%)';
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
            _activeToasts = Math.max(0, _activeToasts - 1);
        }, 300);
    }

    // Pre-set type shortcuts
    toast.success = function (m, opts) { return toast(m, Object.assign({ type: 'success' }, opts)); };
    toast.error   = function (m, opts) { return toast(m, Object.assign({ type: 'error' }, opts)); };
    toast.warning= function (m, opts) { return toast(m, Object.assign({ type: 'warning' }, opts)); };
    toast.info   = function (m, opts) { return toast(m, Object.assign({ type: 'info' }, opts)); };

    /* =========================================================================
     * Confirm Dialog (promise-based modal)
     * ======================================================================= */

    /**
     * Show a confirmation modal. Returns a Promise<boolean>.
     * @param {object} opts
     * @param {string} opts.title
     * @param {string} opts.message
     * @param {string} opts.confirmText  - default "Confirm"
     * @param {string} opts.cancelText   - default "Cancel"
     * @param {string} opts.variant      - danger | warning | default
     * @param {string} opts.placeholder  - if set, user must type this to confirm
     */
    function confirm(opts) {
        opts = opts || {};
        _confirmStack++;
        var zIndex = 9000 + _confirmStack;

        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:' + zIndex + ';display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);animation:cs-fade-in 0.2s ease-out';

            var isDanger = opts.variant === 'danger';
            var isWarning = opts.variant === 'warning';
            var btnColor = isDanger ? '#FF3D3D' : isWarning ? '#d97706' : '#FF3D3D';
            var btnHover = isDanger ? '#E63535' : isWarning ? '#b45309' : '#E63535';

            var modal = document.createElement('div');
            modal.style.cssText = 'position:relative;background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,0.15);width:100%;max-width:440px;margin:0 16px;animation:cs-scale-in 0.2s ease-out';

            var title = opts.title || 'Are you sure?';
            var message = opts.message || '';
            var confirmText = opts.confirmText || 'Confirm';
            var cancelText = opts.cancelText || 'Cancel';

            var html = '';
            html += '<div style="padding:20px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between">';
            html += '<h3 style="font-size:16px;font-weight:600;color:#111827;margin:0;font-family:Inter,system-ui,sans-serif">' + _escape(title) + '</h3>';
            html += '<button data-action="cancel" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:0;font-size:20px;line-height:1">&times;</button>';
            html += '</div>';
            html += '<div style="padding:20px 24px">';
            if (message) {
                html += '<p style="font-size:14px;color:#6b7280;line-height:1.5;margin:0 0 16px;font-family:Inter,system-ui,sans-serif">' + _escape(message) + '</p>';
            }
            if (opts.placeholder) {
                html += '<p style="font-size:14px;color:#374151;margin-bottom:8px;font-family:Inter,system-ui,sans-serif">Type <code style="background:#fef2f2;color:#FF3D3D;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:13px">' + _escape(opts.placeholder) + '</code> to confirm</p>';
                html += '<input type="text" data-confirm-input placeholder="' + _escape(opts.placeholder) + '" style="width:100%;box-sizing:border-box;padding:10px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;outline:none;transition:border-color 0.15s;font-family:monospace" />';
            }
            html += '</div>';
            html += '<div style="padding:16px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:8px">';
            html += '<button data-action="cancel" style="padding:8px 16px;font-size:14px;font-weight:500;color:#374151;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;background:#fff;font-family:Inter,system-ui,sans-serif">' + _escape(cancelText) + '</button>';
            html += '<button data-action="confirm" disabled style="padding:8px 16px;font-size:14px;font-weight:600;color:#fff;background:#d1d5db;border:none;border-radius:8px;cursor:not-allowed;font-family:Inter,system-ui,sans-serif">' + _escape(confirmText) + '</button>';
            html += '</div>';

            modal.innerHTML = html;
            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            var confirmBtn = modal.querySelector('[data-action="confirm"]');
            var cancelBtn = modal.querySelectorAll('[data-action="cancel"]');
            var confirmInput = modal.querySelector('[data-confirm-input]');

            function cleanup() {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.15s';
                setTimeout(function () {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    _confirmStack = Math.max(0, _confirmStack - 1);
                }, 150);
            }

            function doConfirm() {
                confirmBtn.style.background = btnColor;
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.disabled = false;
                confirmBtn.onclick = function () {
                    cleanup();
                    resolve(true);
                };
            }

            if (confirmInput) {
                confirmInput.focus();
                confirmInput.addEventListener('input', function () {
                    if (confirmInput.value === opts.placeholder) {
                        confirmBtn.style.background = btnColor;
                        confirmBtn.style.cursor = 'pointer';
                        confirmBtn.disabled = false;
                        confirmBtn.onclick = function () { cleanup(); resolve(true); };
                    } else {
                        confirmBtn.style.background = '#d1d5db';
                        confirmBtn.style.cursor = 'not-allowed';
                        confirmBtn.disabled = true;
                        confirmBtn.onclick = null;
                    }
                });
                // Enter key
                confirmInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && confirmInput.value === opts.placeholder) {
                        cleanup();
                        resolve(true);
                    }
                });
            } else {
                doConfirm();
            }

            // Cancel buttons
            cancelBtn.forEach(function (btn) {
                btn.onclick = function () { cleanup(); resolve(false); };
            });

            // Overlay click
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) { cleanup(); resolve(false); }
            });

            // Esc key
            document.addEventListener('keydown', function escHandler(e) {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escHandler);
                    cleanup();
                    resolve(false);
                }
            });
        });
    }

    /* =========================================================================
     * Copy to Clipboard
     * ======================================================================= */

    /**
     * Copy text to clipboard with a toast notification.
     * @param {string} text
     * @param {string} successMsg  - default "Copied to clipboard"
     */
    function copy(text, successMsg) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                toast.success(successMsg || 'Copied to clipboard');
            }).catch(function () {
                _fallbackCopy(text);
                toast.success(successMsg || 'Copied to clipboard');
            });
        } else {
            _fallbackCopy(text);
            toast.success(successMsg || 'Copied to clipboard');
        }
    }

    function _fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }

    /* =========================================================================
     * Form Helpers
     * ======================================================================= */

    /**
     * Serialize a form into a plain object.
     * @param {HTMLFormElement} form
     * @returns {object}
     */
    function serializeForm(form) {
        var data = {};
        var fd = new FormData(form);
        fd.forEach(function (value, key) {
            if (data[key] !== undefined) {
                if (!Array.isArray(data[key])) data[key] = [data[key]];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        // Handle checkboxes that are unchecked (FormData doesn't include them)
        form.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            if (!(cb.name in data)) data[cb.name] = false;
            else data[cb.name] = cb.checked;
        });
        return data;
    }

    /**
     * Validate a form against a rules object.
     * Rules: { fieldName: { required: true, min: 3, max: 255, pattern: /regex/, email: true } }
     * Returns { valid: boolean, errors: { field: message } }
     */
    function validateForm(form, rules) {
        var errors = {};
        var valid = true;

        Object.keys(rules).forEach(function (name) {
            var rule = rules[name];
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) return;

            var value = field.value.trim();

            if (rule.required && !value) {
                errors[name] = rule.message || (rule.label || name) + ' is required';
                valid = false;
                return;
            }
            if (value && rule.min && value.length < rule.min) {
                errors[name] = (rule.label || name) + ' must be at least ' + rule.min + ' characters';
                valid = false;
                return;
            }
            if (value && rule.max && value.length > rule.max) {
                errors[name] = (rule.label || name) + ' must be at most ' + rule.max + ' characters';
                valid = false;
                return;
            }
            if (value && rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors[name] = 'Invalid email address';
                valid = false;
                return;
            }
            if (value && rule.pattern && !rule.pattern.test(value)) {
                errors[name] = rule.message || 'Invalid format for ' + (rule.label || name);
                valid = false;
                return;
            }
        });

        return { valid: valid, errors: errors };
    }

    /**
     * Show inline field errors from validateForm result.
     */
    function showErrors(form, errors) {
        // Clear previous
        form.querySelectorAll('[data-error]').forEach(function (el) { el.remove(); });

        Object.keys(errors).forEach(function (name) {
            var field = form.querySelector('[name="' + name + '"]');
            if (!field) return;

            field.style.borderColor = '#FF3D3D';
            field.addEventListener('input', function () {
                field.style.borderColor = '';
            }, { once: true });

            var msg = document.createElement('p');
            msg.setAttribute('data-error', '');
            msg.style.cssText = 'color:#FF3D3D;font-size:12px;margin-top:4px';
            msg.textContent = errors[name];
            field.parentNode.appendChild(msg);
        });
    }

    /* =========================================================================
     * Button Loading State
     * ======================================================================= */

    /**
     * Wrap an async action with loading state on a button.
     * Disables button, shows spinner, re-enables when done.
     * @param {HTMLElement} button
     * @param {function} asyncFn  - must return a Promise
     * @param {string} loadingText - optional text to show while loading
     */
    function withLoading(button, asyncFn, loadingText) {
        var originalHTML = button.innerHTML;
        var originalDisabled = button.disabled;

        button.disabled = true;
        button.style.opacity = '0.7';
        button.style.cursor = 'wait';
        if (loadingText) {
            button.innerHTML = '<svg style="display:inline-block;width:16px;height:16px;margin-right:6px;animation:cs-spin 0.8s linear infinite" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>' + loadingText;
        }

        return Promise.resolve()
            .then(asyncFn)
            .finally(function () {
                button.innerHTML = originalHTML;
                button.disabled = originalDisabled;
                button.style.opacity = '';
                button.style.cursor = '';
            });
    }

    /* =========================================================================
     * Click Outside Handler
     * ======================================================================= */

    /**
     * Call a handler when user clicks outside the given element.
     * Returns a cleanup function to remove the listener.
     */
    function onClickOutside(element, handler) {
        function listener(e) {
            if (!element.contains(e.target)) handler(e);
        }
        // Use setTimeout to avoid catching the opening click
        setTimeout(function () {
            document.addEventListener('click', listener);
        }, 0);
        return function () { document.removeEventListener('click', listener); };
    }

    /**
     * Auto-setup the user menu dropdown (the standard top-right avatar).
     * @param {object} opts
     * @param {string} opts.trigger  - selector for the trigger element (default: '.cs-user-trigger')
     * @param {string} opts.menu     - selector for the menu element (default: '#userMenu')
     */
    function setupUserMenu(opts) {
        opts = opts || {};
        var triggerSel = opts.trigger || '.cs-user-trigger';
        var menuSel = opts.menu || '#userMenu';
        var trigger = document.querySelector(triggerSel);
        var menu = document.querySelector(menuSel);
        if (!trigger || !menu) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    }

    /* =========================================================================
     * Format Helpers
     * ======================================================================= */

    /**
     * Format a date string or Date object into "Mon DD, YYYY, HH:MM AM/PM".
     */
    function formatDate(date) {
        var d = typeof date === 'string' ? new Date(date) : date;
        if (isNaN(d.getTime())) return '—';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var h = d.getHours();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        var m = String(d.getMinutes()).padStart(2, '0');
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ', ' + h + ':' + m + ' ' + ampm;
    }

    /**
     * Format a date as relative time ("2 min ago", "3 hours ago").
     */
    function timeAgo(date) {
        var d = typeof date === 'string' ? new Date(date) : date;
        var seconds = Math.floor((Date.now() - d.getTime()) / 1000);
        if (isNaN(seconds)) return '—';
        if (seconds < 60) return seconds + ' sec ago';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + ' min ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + (hours === 1 ? ' hour ago' : ' hours ago');
        var days = Math.floor(hours / 24);
        if (days < 30) return days + (days === 1 ? ' day ago' : ' days ago');
        var months = Math.floor(days / 30);
        if (months < 12) return months + (months === 1 ? ' month ago' : ' months ago');
        var years = Math.floor(months / 12);
        return years + (years === 1 ? ' year ago' : ' years ago');
    }

    /**
     * Truncate a string with ellipsis.
     */
    function truncate(str, length) {
        if (!str) return '';
        if (str.length <= length) return str;
        return str.slice(0, length) + '...';
    }

    /**
     * Format a UUID for display (first 12 chars + ellipsis).
     */
    function formatUUID(uuid) {
        if (!uuid) return '—';
        if (uuid.length <= 16) return uuid;
        return uuid.slice(0, 8) + '-' + uuid.slice(8, 12) + '...';
    }

    /**
     * Generate a random ID with a prefix.
     */
    function generateId(prefix) {
        var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        var id = '';
        for (var i = 0; i < 8; i++) id += chars[Math.floor(Math.random() * chars.length)];
        return (prefix || 'id') + '_' + id;
    }

    /**
     * Generate a CodeSign-style license key: CS-XXXX-XXXX-XXXX
     */
    function generateKey() {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        function block() {
            var s = '';
            for (var i = 0; i < 4; i++) s += chars[Math.floor(Math.random() * chars.length)];
            return s;
        }
        return 'CS-' + block() + '-' + block() + '-' + block();
    }

    /**
     * Format bytes into human-readable string.
     */
    function formatBytes(bytes) {
        if (!bytes) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 10) / 10 + ' ' + units[i];
    }

    /**
     * Mask a string, showing only the first n and last m characters.
     */
    function mask(str, showFirst, showLast) {
        if (!str) return '';
        showFirst = showFirst || 4;
        showLast = showLast || 4;
        if (str.length <= showFirst + showLast) return str;
        return str.slice(0, showFirst) + '•'.repeat(Math.min(str.length - showFirst - showLast, 20)) + str.slice(-showLast);
    }

    /* =========================================================================
     * Status Badge Helper
     * ======================================================================= */

    var STATUS_STYLES = {
        active:      { bg: '#f0fdf4', text: '#166534', label: 'Active' },
        inactive:    { bg: '#f5f5f4', text: '#737373', label: 'Inactive' },
        disabled:    { bg: '#f5f5f4', text: '#737373', label: 'Disabled' },
        suspended:   { bg: '#fffbeb', text: '#92400e', label: 'Suspended' },
        expired:     { bg: '#fef2f2', text: '#991b1b', label: 'Expired' },
        revoked:     { bg: '#fef2f2', text: '#991b1b', label: 'Revoked' },
        pending:     { bg: '#fffbeb', text: '#92400e', label: 'Pending' },
        failing:     { bg: '#fef2f2', text: '#991b1b', label: 'Failing' },
        success:     { bg: '#f0fdf4', text: '#166534', label: 'Success' },
    };

    /**
     * Create a status badge element.
     * @param {string} status
     * @param {string} label  - override display label
     * @returns {HTMLElement}
     */
    function statusBadge(status, label) {
        var style = STATUS_STYLES[status] || { bg: '#f5f5f4', text: '#737373', label: status };
        var badge = document.createElement('span');
        badge.style.cssText = 'display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;font-size:12px;font-weight:500;background:' + style.bg + ';color:' + style.text;
        badge.textContent = label || style.label;
        return badge;
    }

    /**
     * Return HTML string for a status badge (for use in innerHTML).
     */
    function statusBadgeHTML(status, label) {
        var style = STATUS_STYLES[status] || { bg: '#f5f5f4', text: '#737373', label: status };
        return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;font-size:12px;font-weight:500;background:' + style.bg + ';color:' + style.text + '">' + _escape(label || style.label) + '</span>';
    }

    /* =========================================================================
     * Utility Functions
     * ======================================================================= */

    function _escape(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function debounce(fn, wait) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, wait || 300);
        };
    }

    function throttle(fn, wait) {
        var last = 0;
        return function () {
            var now = Date.now();
            if (now - last >= (wait || 300)) {
                fn.apply(this, arguments);
                last = now;
            }
        };
    }

    /**
     * Simple HTTP request (Promise-based fetch wrapper).
     * @param {string} method - GET | POST | PATCH | DELETE
     * @param {string} url
     * @param {object} data   - body for POST/PATCH
     * @returns {Promise}
     */
    function api(method, url, data) {
        var opts = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (data) opts.body = JSON.stringify(data);
        return fetch(url, opts).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            var ct = res.headers.get('content-type') || '';
            return ct.includes('application/json') ? res.json() : res.text();
        });
    }

    /**
     * Query param helper — parse or build URL query strings.
     * CS.query({ page: 2, limit: 20 })  => "?page=2&limit=20"
     * CS.query()                        => { page: "2", ... } (from current URL)
     */
    function query(params) {
        if (params === undefined) {
            var obj = {};
            new URLSearchParams(window.location.search).forEach(function (v, k) { obj[k] = v; });
            return obj;
        }
        var qs = new URLSearchParams(params).toString();
        return qs ? '?' + qs : '';
    }

    /* =========================================================================
     * Modal Helper (generic, not just confirm)
     * ======================================================================= */

    /**
     * Open a modal with custom HTML content.
     * @param {object} opts
     * @param {string} opts.title
     * @param {string} opts.body  - HTML string
     * @param {string} opts.size  - sm | md | lg (default md)
     * @param {boolean} opts.closable - default true
     * @returns {object} { element, close, setTitle, setBody }
     */
    function modal(opts) {
        opts = opts || {};
        var sizes = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-2xl' };
        var maxWidth = opts.size === 'sm' ? '380px' : opts.size === 'lg' ? '640px' : '440px';

        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);animation:cs-fade-in 0.2s ease-out;padding:16px';

        var dialog = document.createElement('div');
        dialog.style.cssText = 'position:relative;background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,0.15);width:100%;max-width:' + maxWidth + ';animation:cs-scale-in 0.2s ease-out;max-height:90vh;overflow-y:auto';

        var html = '';
        html += '<div style="padding:20px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;border-radius:12px 12px 0 0">';
        html += '<h3 data-modal-title style="font-size:16px;font-weight:600;color:#111827;margin:0;font-family:Inter,system-ui,sans-serif">' + _escape(opts.title || '') + '</h3>';
        if (opts.closable !== false) {
            html += '<button data-modal-close style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:0;font-size:20px;line-height:1">&times;</button>';
        }
        html += '</div>';
        html += '<div data-modal-body style="padding:24px;font-family:Inter,system-ui,sans-serif">' + (opts.body || '') + '</div>';

        dialog.innerHTML = html;
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        function close() {
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.15s';
            setTimeout(function () { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }, 150);
        }

        overlay.querySelector('[data-modal-close]')?.addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay && opts.closable !== false) close(); });

        return {
            element: dialog,
            close: close,
            setTitle: function (t) { dialog.querySelector('[data-modal-title]').textContent = t; },
            setBody: function (b) { dialog.querySelector('[data-modal-body]').innerHTML = b; },
        };
    }

    /* =========================================================================
     * Init: inject CSS animations
     * ======================================================================= */

    var styleEl = document.createElement('style');
    styleEl.textContent = [
        '@keyframes cs-slide-in {',
        '  from { opacity: 0; transform: translateX(100%); }',
        '  to { opacity: 1; transform: translateX(0); }',
        '}',
        '@keyframes cs-fade-in {',
        '  from { opacity: 0; }',
        '  to { opacity: 1; }',
        '}',
        '@keyframes cs-scale-in {',
        '  from { opacity: 0; transform: scale(0.95); }',
        '  to { opacity: 1; transform: scale(1); }',
        '}',
        '@keyframes cs-spin {',
        '  to { transform: rotate(360deg); }',
        '}',
    ].join('\n');
    document.head.appendChild(styleEl);

    /* =========================================================================
     * Public API
     * ======================================================================= */

    window.CS = {
        // Toast
        toast:         toast,

        // Dialogs
        confirm:       confirm,
        modal:         modal,

        // Clipboard
        copy:          copy,

        // Forms
        serializeForm: serializeForm,
        validateForm:  validateForm,
        showErrors:    showErrors,

        // UI helpers
        withLoading:   withLoading,
        onClickOutside:onClickOutside,
        setupUserMenu: setupUserMenu,
        statusBadge:   statusBadge,
        statusBadgeHTML: statusBadgeHTML,

        // Formatting
        formatDate:    formatDate,
        timeAgo:       timeAgo,
        truncate:      truncate,
        formatUUID:    formatUUID,
        formatBytes:   formatBytes,
        mask:          mask,
        generateId:    generateId,
        generateKey:   generateKey,

        // Utilities
        debounce:      debounce,
        throttle:      throttle,
        api:           api,
        query:         query,
        escape:        _escape,
    };

})();