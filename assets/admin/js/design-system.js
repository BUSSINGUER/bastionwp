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
        var steps = document.querySelector('.bastionwp-wrap .bastionwp-wizard-steps');

        if (!steps || steps.dataset.bwpEnhanced === '1') {
            return;
        }

        steps.dataset.bwpEnhanced = '1';

        var toolbar = document.createElement('div');
        toolbar.className = 'bastionwp-wizard-filterbar';

        var all = makeButton('Todas', 'button bastionwp-filter-chip is-active');
        var required = makeButton('Obrigatórias', 'button bastionwp-filter-chip');
        var recommended = makeButton('Recomendadas', 'button bastionwp-filter-chip');

        toolbar.appendChild(all);
        toolbar.appendChild(required);
        toolbar.appendChild(recommended);
        steps.parentNode.insertBefore(toolbar, steps);

        var buttons = [all, required, recommended];

        function apply(mode) {
            buttons.forEach(function (button) {
                button.classList.remove('is-active');
            });

            if (mode === 'required') {
                required.classList.add('is-active');
            } else if (mode === 'recommended') {
                recommended.classList.add('is-active');
            } else {
                all.classList.add('is-active');
            }

            steps.querySelectorAll('.bastionwp-wizard-step').forEach(function (step) {
                var isRequired = !!step.querySelector('.bastionwp-required-badge');
                var isRecommended = !!step.querySelector('.bastionwp-optional-badge');

                step.hidden =
                    (mode === 'required' && !isRequired) ||
                    (mode === 'recommended' && !isRecommended);
            });
        }

        all.addEventListener('click', function () { apply('all'); });
        required.addEventListener('click', function () { apply('required'); });
        recommended.addEventListener('click', function () { apply('recommended'); });
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
