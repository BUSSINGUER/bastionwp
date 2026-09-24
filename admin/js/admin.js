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
                applyProfile(radio.value);
            });
        });

        var checked = root.querySelector('input[name="hardening_profile"]:checked');
        if (checked) {
            applyProfile(checked.value);
        }
    });
})();
