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

    function init() {
        initAccessTools();
        initWizardFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
