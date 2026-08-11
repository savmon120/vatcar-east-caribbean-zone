(function (Drupal) {
    'use strict';

    Drupal.behaviors.waitlistAjaxTabs = {
        attach: function () {
            const views = document.querySelectorAll('.view-id-training_waitlist');

            views.forEach(function (view) {
                const filtersWrapper = view.querySelector('.view-filters');
                const form = view.querySelector('form.views-exposed-form');

                if (!form || !filtersWrapper) return;

                if (filtersWrapper.classList.contains('tabs-processed')) return;
                filtersWrapper.classList.add('tabs-processed');

                const select = form.querySelector('select');
                const submitBtn = form.querySelector('.form-submit') || form.querySelector('input[type="submit"]');

                if (!select || !submitBtn) return;

                filtersWrapper.style.display = 'none';

                const tabsContainer = document.createElement('ul');
                tabsContainer.className = 'nav nav-tabs custom-waitlist-tabs';

                Array.from(select.options).forEach(function(option) {
                    if (option.value === 'All') return;

                    const li = document.createElement('li');
                    li.className = 'nav-item';

                    const a = document.createElement('a');
                    a.className = option.selected ? 'nav-link active is-active' : 'nav-link';
                    a.innerText = option.text;
                    a.href = '#';

                    a.addEventListener('click', function(e) {
                        e.preventDefault();

                        if (this.classList.contains('active')) return;

                        tabsContainer.querySelectorAll('a').forEach(tab => tab.classList.remove('active', 'is-active'));
                        this.classList.add('active', 'is-active');

                        select.value = option.value;
                        submitBtn.click();
                    });

                    li.appendChild(a);
                    tabsContainer.appendChild(li);
                });

                filtersWrapper.parentNode.insertBefore(tabsContainer, filtersWrapper);
            });
        }
    };
})(Drupal);