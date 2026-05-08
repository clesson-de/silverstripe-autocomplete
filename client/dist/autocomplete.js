(function () {
    'use strict';

    function initField(root) {
        var input = root.querySelector('.ss-autocomplete-field__input');
        var list = root.querySelector('.ss-autocomplete-field__list');

        if (!input || !list) {
            return;
        }

        var endpoint = root.dataset.endpoint || '';
        var sourceType = root.dataset.sourceType || 'model';
        var sourceClass = root.dataset.sourceClass || '';
        var sourceProperty = root.dataset.sourceProperty || '';
        var callbackKey = root.dataset.callbackKey || '';
        var limit = parseInt(root.dataset.limit || '10', 10);
        var minChars = parseInt(root.dataset.minChars || '2', 10);
        var activeIndex = -1;

        function hideList() {
            list.hidden = true;
            list.innerHTML = '';
            activeIndex = -1;
        }

        function render(items) {
            if (!items.length) {
                hideList();
                return;
            }

            list.innerHTML = '';
            items.forEach(function (value) {
                var li = document.createElement('li');
                li.className = 'ss-autocomplete-field__item';
                li.textContent = value;
                li.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    input.value = value;
                    hideList();
                });
                list.appendChild(li);
            });
            list.hidden = false;
            activeIndex = -1;
        }

        function updateActiveItem() {
            var items = list.querySelectorAll('.ss-autocomplete-field__item');
            items.forEach(function (item, index) {
                item.classList.toggle('is-active', index === activeIndex);
            });
        }

        function requestSuggestions(query) {
            var params = new URLSearchParams({
                q: query,
                sourceType: sourceType,
                sourceClass: sourceClass,
                sourceProperty: sourceProperty,
                callbackKey: callbackKey,
                limit: String(limit)
            });

            return fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (payload) { return Array.isArray(payload.results) ? payload.results : []; })
                .catch(function () { return []; });
        }

        input.addEventListener('input', function () {
            var query = input.value.trim();

            if (query.length < minChars) {
                hideList();
                return;
            }

            requestSuggestions(query).then(render);
        });

        input.addEventListener('keydown', function (event) {
            var items = list.querySelectorAll('.ss-autocomplete-field__item');

            if (!items.length || list.hidden) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                updateActiveItem();
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
                updateActiveItem();
            }

            if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                input.value = items[activeIndex].textContent || '';
                hideList();
            }

            if (event.key === 'Escape') {
                hideList();
            }
        });

        input.addEventListener('blur', function () {
            window.setTimeout(hideList, 120);
        });
    }

    function initAll() {
        document.querySelectorAll('.ss-autocomplete-field').forEach(initField);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();

