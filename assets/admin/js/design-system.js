(function () {
    'use strict';

    function makeButton(label, className) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = className || 'button';
        button.textContent = label;
        return button;
    }

    function initAccessTools() {
        var lists = document.querySelectorAll('.bastionwp-wrap .bastionwp-menu-list');

        lists.forEach(function (list) {
            if (list.dataset.bwpEnhanced === '1') {
                return;
            }

            list.dataset.bwpEnhanced = '1';

            var toolbar = document.createElement('div');
            toolbar.className = 'bastionwp-access-tools';

            var search = document.createElement('input');
            search.type = 'search';
            search.className = 'bastionwp-menu-search';
            search.placeholder = 'Buscar menus...';
            search.setAttribute('aria-label', 'Buscar menus adicionais');

            var selectAll = makeButton('Selecionar visíveis', 'button');
            var clear = makeButton('Limpar seleção', 'button');

            toolbar.appendChild(search);
            toolbar.appendChild(selectAll);
            toolbar.appendChild(clear);
            list.parentNode.insertBefore(toolbar, list);

            function options() {
                return Array.prototype.slice.call(list.querySelectorAll('.bastionwp-menu-option'));
            }

            search.addEventListener('input', function () {
                var query = search.value.trim().toLocaleLowerCase('pt-BR');

                options().forEach(function (item) {
                    var text = item.textContent.toLocaleLowerCase('pt-BR');
                    item.hidden = query !== '' && text.indexOf(query) === -1;
                });
            });

            selectAll.addEventListener('click', function () {
                options().forEach(function (item) {
                    if (item.hidden) {
                        return;
                    }

                    var checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });

            clear.addEventListener('click', function () {
                options().forEach(function (item) {
                    var checkbox = item.querySelector('input[type="checkbox"]');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        });
    }

    function initWizardFilters() {
        var steps = document.querySelector('.bastionwp-wrap .bastionwp-wizard-steps-modern');
        var filters = document.querySelectorAll('.bastionwp-wrap [data-wizard-filter]');

        if (!steps || filters.length === 0 || steps.dataset.bwpEnhanced === '1') {
            return;
        }

        steps.dataset.bwpEnhanced = '1';

        function apply(mode) {
            filters.forEach(function (button) {
                button.classList.toggle(
                    'is-active',
                    button.getAttribute('data-wizard-filter') === mode
                );
            });

            steps.querySelectorAll('.bastionwp-wizard-step-modern').forEach(function (step) {
                var type = step.getAttribute('data-wizard-type');

                step.hidden =
                    (mode === 'required' && type !== 'required') ||
                    (mode === 'recommended' && type !== 'recommended');
            });
        }

        filters.forEach(function (button) {
            button.addEventListener('click', function () {
                apply(button.getAttribute('data-wizard-filter') || 'all');
            });
        });

        apply('all');
    }

    function initDeveloperRiskZone() {
        var zone = document.querySelector('[data-bastionwp-risk-zone]');

        if (!zone || zone.dataset.bwpEnhanced === '1') {
            return;
        }

        zone.dataset.bwpEnhanced = '1';

        var unlock = zone.querySelector('[data-bastionwp-risk-unlock]');
        var fieldset = zone.querySelector('[data-bastionwp-risk-fieldset]');
        var form = zone.querySelector('[data-bastionwp-risk-form]');

        if (!unlock || !fieldset) {
            return;
        }

        unlock.addEventListener('click', function () {
            if (!fieldset.disabled) {
                fieldset.disabled = true;
                zone.classList.remove('is-unlocked');
                unlock.innerHTML = '<span class="dashicons dashicons-lock" aria-hidden="true"></span>Desbloquear alteração';
                return;
            }

            var confirmed = window.confirm(
                'Esta é uma alteração sensível. Desbloquear a edição do Developer Principal?'
            );

            if (!confirmed) {
                return;
            }

            fieldset.disabled = false;
            zone.classList.add('is-unlocked');
            unlock.innerHTML = '<span class="dashicons dashicons-unlock" aria-hidden="true"></span>Bloquear novamente';
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                if (fieldset.disabled) {
                    event.preventDefault();
                    return;
                }

                var confirmed = window.confirm(
                    'Confirmar alteração do Developer Principal? Esta conta controla áreas técnicas e de segurança do BastionWP.'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }
    }

    function initHardeningSubnav() {
        var nav = document.querySelector('.bastionwp-hardening-subnav');

        if (!nav || nav.dataset.bwpEnhanced === '1') {
            return;
        }

        nav.dataset.bwpEnhanced = '1';

        var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]'));

        links.forEach(function (link) {
            link.addEventListener('click', function () {
                links.forEach(function (item) {
                    item.classList.remove('is-active');
                });

                link.classList.add('is-active');
            });
        });
    }

    function init() {
        initAccessTools();
        initWizardFilters();
        initDeveloperRiskZone();
        initHardeningSubnav();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
