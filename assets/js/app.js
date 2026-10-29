document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('[data-nav]');
  const toggle = document.querySelector('[data-nav-toggle]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
  }

  const range = document.querySelector('[data-budget-range]');
  const number = document.querySelector('[data-budget-number]');
  const label = document.querySelector('[data-budget-label]');

  const formatInr = (amount) => '₹' + Number(amount).toLocaleString('en-IN');

  const syncBudget = (source) => {
    if (!range || !number) return;
    if (source === range) {
      number.value = Number(range.value) > 0 ? range.value : '';
    } else {
      const next = Number(number.value || 0);
      const max = Number(range.max || 20000);
      range.value = String(Math.min(Math.max(next, 0), max));
    }
    if (label) {
      const value = Number(number.value || 0);
      label.textContent = value > 0 ? formatInr(value) : 'Any amount';
    }
  };

  range?.addEventListener('input', () => syncBudget(range));
  number?.addEventListener('input', () => syncBudget(number));

  const filters = document.querySelector('[data-filters]');
  if (filters) {
    filters.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => filters.requestSubmit());
    });
    range?.addEventListener('change', () => {
      syncBudget(range);
      filters.requestSubmit();
    });
    number?.addEventListener('change', () => {
      syncBudget(number);
      filters.requestSubmit();
    });
  }

  const designSelect = document.querySelector('[data-design-select]');
  const amount = document.querySelector('[data-amount]');
  const summaryTitle = document.querySelector('[data-summary-title]');
  const summaryCategory = document.querySelector('[data-summary-category]');
  const summaryTime = document.querySelector('[data-summary-time]');
  if (designSelect && amount) {
    const updateDesign = () => {
      const option = designSelect.selectedOptions[0];
      const price = Number(option?.dataset.price || 0);
      amount.textContent = price > 0 ? formatInr(price) : '—';
      if (summaryTitle) summaryTitle.textContent = option?.dataset.title || '';
      if (summaryCategory) summaryCategory.textContent = option?.dataset.category || '';
      if (summaryTime) summaryTime.textContent = option?.dataset.hours || '';
    };
    designSelect.addEventListener('change', updateDesign);
    updateDesign();
  }

  document.querySelectorAll('[data-dismiss]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[data-flash]')?.remove());
  });
});
