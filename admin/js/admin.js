(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var root = document.getElementById('bastionwp-hardening-root');
        if (!root || typeof window.BastionWPHardeningData === 'undefined') {
            return;
        }

        var data = window.BastionWPHardeningData || {};
        var profiles = data.profiles || {};
        var radios = root.querySelectorAll('input[name="hardening_profile"]');
        var summaryTitle = document.getElementById('bastionwp-hardening-summary-title');
        var summaryList = document.getElementById('bastionwp-hardening-summary-list');
        var compatibilityTitle = document.getElementById('bastionwp-hardening-compatibility-title');
        var compatibilityList = document.getElementById('bastionwp-hardening-compatibility-list');
        var profileDescription = document.getElementById('bastionwp-hardening-profile-description');

        var rulesMap = {
            block_file_editors: document.querySelector('[data-hardening-rule="block_file_editors"]'),
            disable_xmlrpc: document.querySelector('[data-hardening-rule="disable_xmlrpc"]'),
            disable_application_passwords: document.querySelector('[data-hardening-rule="disable_application_passwords"]'),
            hide_wordpress_version: document.querySelector('[data-hardening-rule="hide_wordpress_version"]'),
            generic_login_errors: document.querySelector('[data-hardening-rule="generic_login_errors"]'),
            block_public_rest_users: document.querySelector('[data-hardening-rule="block_public_rest_users"]'),
            block_manual_infrastructure_changes: document.querySelector('[data-hardening-rule="block_manual_infrastructure_changes"]')
        };

        function renderList(container, items) {
            if (!container) return;
            container.innerHTML = '';
            (items || []).forEach(function (item) {
                var li = document.createElement('li');
                li.textContent = item;
                container.appendChild(li);
            });
        }

        function updateRule(element, isEnabled, labels) {
            if (!element) return;
            var badge = element.querySelector('.bastionwp-rule-badge');
            var helper = element.querySelector('.bastionwp-rule-helper');

            if (badge) {
                badge.textContent = isEnabled ? labels.enabled : labels.disabled;
                badge.classList.remove('bastionwp-badge-blocked', 'bastionwp-badge-allowed');
                badge.classList.add(isEnabled ? 'bastionwp-badge-blocked' : 'bastionwp-badge-allowed');
            }

            if (helper) {
                helper.textContent = isEnabled ? labels.enabledHelper : labels.disabledHelper;
            }
        }

        function applyProfile(profileKey) {
            var profile = profiles[profileKey];
            if (!profile) return;

            if (summaryTitle) {
                summaryTitle.textContent = 'O que muda ao aplicar ' + profile.label;
            }

            if (compatibilityTitle) {
                compatibilityTitle.textContent = profile.compatibilityTitle;
            }

            if (profileDescription) {
                profileDescription.textContent = profile.description;
            }

            renderList(summaryList, profile.summary || []);
            renderList(compatibilityList, profile.compatibility || []);

            var settings = profile.settings || {};
            updateRule(rulesMap.block_file_editors, !!settings.block_file_editors, {
                enabled: 'Bloqueado',
                disabled: 'Permitido',
                enabledHelper: 'Editor de arquivos de plugins e temas ficará indisponível.',
                disabledHelper: 'Editor de arquivos permanecerá disponível.'
            });

            updateRule(rulesMap.disable_xmlrpc, !!settings.disable_xmlrpc, {
                enabled: 'Bloqueado',
                disabled: 'Permitido',
                enabledHelper: 'Os métodos XML-RPC do WordPress ficam indisponíveis; o endpoint ainda pode responder com uma mensagem de falha.',
                disabledHelper: 'XML-RPC continuará disponível.'
            });

            updateRule(rulesMap.disable_application_passwords, !!settings.disable_application_passwords, {
                enabled: 'Bloqueadas',
                disabled: 'Permitidas',
                enabledHelper: 'Application Passwords não poderão ser usadas.',
                disabledHelper: 'Application Passwords continuarão disponíveis.'
            });

            updateRule(rulesMap.hide_wordpress_version, !!settings.hide_wordpress_version, {
                enabled: 'Ocultada',
                disabled: 'Padrão WordPress',
                enabledHelper: 'A versão do WordPress será ocultada no HTML.',
                disabledHelper: 'A saída padrão do WordPress será mantida.'
            });

            updateRule(rulesMap.generic_login_errors, !!settings.generic_login_errors, {
                enabled: 'Mensagem genérica',
                disabled: 'Padrão WordPress',
                enabledHelper: 'Erros de login exibirão texto genérico.',
                disabledHelper: 'Erros padrão do WordPress serão mantidos.'
            });

            updateRule(rulesMap.block_public_rest_users, !!settings.block_public_rest_users, {
                enabled: 'Bloqueado sem login',
                disabled: 'Padrão WordPress',
                enabledHelper: 'A listagem pública de usuários pela REST API será bloqueada.',
                disabledHelper: 'A REST API seguirá o comportamento padrão do WordPress.'
            });

            updateRule(rulesMap.block_manual_infrastructure_changes, !!settings.block_manual_infrastructure_changes, {
                enabled: 'Bloqueadas',
                disabled: 'Permitidas ao Developer',
                enabledHelper: 'Alterações manuais de plugins, temas e core serão bloqueadas.',
                disabledHelper: 'Manutenção manual continuará disponível ao Developer.'
            });
        }

        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                radios.forEach(function (item) {
                    var card = item.closest('.bastionwp-hardening-profile');
                    if (card) card.classList.toggle('is-selected', item.checked);
                });
                applyProfile(radio.value);
            });
        });

        var checked = root.querySelector('input[name="hardening_profile"]:checked');
        if (checked) {
            radios.forEach(function (item) {
                var card = item.closest('.bastionwp-hardening-profile');
                if (card) card.classList.toggle('is-selected', item.checked);
            });
            applyProfile(checked.value);
        }
    });
})();

(function () {
    'use strict';

    function initBastionAuthSession() {
        var root = document.querySelector('[data-bastionwp-auth-session]');
        var data = window.BastionWPAuthData || {};
        if (!root || !data.authenticated) {
            return;
        }

        var timer = root.querySelector('[data-bastionwp-auth-timer]');
        var idleExpiresAt = Number(root.getAttribute('data-idle-expires') || data.idleExpiresAt || 0);
        var hardExpiresAt = Number(root.getAttribute('data-hard-expires') || data.hardExpiresAt || 0);
        var lastTouchSent = 0;
        var touchPending = false;
        var expired = false;

        function effectiveExpiry() {
            if (!idleExpiresAt) {
                return hardExpiresAt;
            }
            if (!hardExpiresAt) {
                return idleExpiresAt;
            }
            return Math.min(idleExpiresAt, hardExpiresAt);
        }

        function renderTimer() {
            if (!timer || expired) {
                return;
            }
            var remaining = Math.max(0, effectiveExpiry() - Math.floor(Date.now() / 1000));
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            timer.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            root.classList.toggle('is-expiring', remaining > 0 && remaining <= 120);
            if (remaining <= 0) {
                expired = true;
                window.setTimeout(function () {
                    window.location.reload();
                }, 250);
            }
        }

        function sendTouch() {
            var now = Date.now();
            if (touchPending || expired || now - lastTouchSent < 60000) {
                return;
            }
            lastTouchSent = now;
            touchPending = true;

            var body = new URLSearchParams();
            body.set('action', 'bastionwp_auth_touch');
            body.set('nonce', data.nonce || '');

            window.fetch(data.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            }).then(function (response) {
                return response.json().catch(function () { return null; });
            }).then(function (payload) {
                if (!payload || !payload.success || !payload.data || !payload.data.state) {
                    expired = true;
                    window.location.reload();
                    return;
                }
                var state = payload.data.state;
                idleExpiresAt = Number(state.idle_expires_at || idleExpiresAt);
                hardExpiresAt = Number(state.hard_expires_at || hardExpiresAt);
                root.setAttribute('data-idle-expires', String(idleExpiresAt));
                root.setAttribute('data-hard-expires', String(hardExpiresAt));
                renderTimer();
            }).catch(function () {
                // Falha de rede não estende a sessão. O servidor continua sendo a autoridade.
            }).finally(function () {
                touchPending = false;
            });
        }

        ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function (eventName) {
            document.addEventListener(eventName, sendTouch, { passive: true });
        });

        renderTimer();
        window.setInterval(renderTimer, 1000);
    }

    function initRestModeCards() {
        var root = document.querySelector('[data-bastionwp-rest-modes]');
        if (!root) {
            return;
        }

        var radios = Array.prototype.slice.call(root.querySelectorAll('input[type="radio"][name="security[rest_mode]"]'));
        var cards = Array.prototype.slice.call(root.querySelectorAll('[data-rest-mode-card]'));
        var summaries = Array.prototype.slice.call(document.querySelectorAll('[data-rest-summary]'));
        var inventory = document.querySelector('[data-rest-inventory]');
        var blockCount = document.querySelector('[data-rest-block-count]');
        var form = root.closest('form');

        function selectedMode() {
            var checked = radios.find(function (radio) { return radio.checked; });
            return checked ? checked.value : 'observe';
        }

        function updateBlockedCount() {
            if (!inventory || !blockCount) {
                return;
            }
            var checkboxes = Array.prototype.slice.call(inventory.querySelectorAll('input[data-rest-namespace]'));
            var blocked = checkboxes.filter(function (input) {
                return !input.disabled && !input.checked;
            }).length;
            blockCount.textContent = blocked === 1
                ? '1 namespace será bloqueado.'
                : blocked + ' namespaces serão bloqueados.';
        }

        function sync() {
            var mode = selectedMode();
            cards.forEach(function (card) {
                card.classList.toggle('is-selected', card.getAttribute('data-rest-mode-card') === mode);
            });
            summaries.forEach(function (summary) {
                summary.hidden = summary.getAttribute('data-rest-summary') !== mode;
            });
            if (inventory) {
                inventory.classList.toggle('is-readonly', mode !== 'allowlist');
            }
            updateBlockedCount();
        }

        radios.forEach(function (radio) {
            radio.addEventListener('change', sync);
        });
        if (inventory) {
            inventory.querySelectorAll('input[data-rest-namespace]').forEach(function (input) {
                input.addEventListener('change', updateBlockedCount);
            });
        }
        if (form) {
            form.addEventListener('submit', function (event) {
                if (selectedMode() !== 'allowlist' || !inventory) {
                    return;
                }
                var blocked = Array.prototype.slice.call(inventory.querySelectorAll('input[data-rest-namespace]')).filter(function (input) {
                    return !input.disabled && !input.checked;
                }).length;
                if (blocked > 0 && !window.confirm('A Allowlist avançada bloqueará ' + blocked + ' namespace(s) REST. Confirma que o site foi homologado com esta seleção?')) {
                    event.preventDefault();
                }
            });
        }

        sync();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initBastionAuthSession();
        initRestModeCards();
    });
})();
