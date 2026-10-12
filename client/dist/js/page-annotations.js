(function () {
  'use strict';

  const typeSelector = '[name="SchemaAnnotationType"]';
  const valuesSelector = '[name="SchemaAnnotationValues"]';
  const fieldSelector = '[data-schema-type]';

  function getControls(form) {
    const type = form.querySelector(typeSelector);
    const storage = form.querySelector(valuesSelector);
    return type && storage ? {type, storage} : null;
  }

  function holder(field) {
    return field.closest('.form-group.field') || field;
  }

  function show(element, visible) {
    // Silverstripe/Bootstrap field styling can override the hidden attribute.
    element.style.setProperty('display', visible ? '' : 'none', visible ? '' : 'important');
    element.hidden = !visible;
  }

  function updateVisibility(form) {
    const controls = getControls(form);
    if (!controls) return;

    const activeType = controls.type.value;
    form.querySelectorAll(fieldSelector).forEach(field => {
      let visible = field.dataset.schemaType === activeType;
      const part = field.dataset.schemaEntityPart;

      if (visible && part && part !== 'mode') {
        const prefix = field.name.replace(/_(id|type|name)$/, '');
        const modeField = form.elements.namedItem(prefix + '_mode');
        visible = !!modeField && modeField.value === (part === 'id' ? 'existing' : 'inline');
      }

      show(holder(field), visible);
    });
  }

  function serialize(form) {
    const controls = getControls(form);
    if (!controls) return;

    const values = {};
    const entities = {};
    form.querySelectorAll(fieldSelector).forEach(field => {
      if (field.dataset.schemaType !== controls.type.value) return;
      const property = field.dataset.schemaProperty;
      if (!property) return;

      const part = field.dataset.schemaEntityPart;
      if (part) {
        (entities[property] ||= {})[part] = field.value;
      } else if (field.value !== '') {
        values[property] = field.value;
      }
    });

    Object.entries(entities).forEach(([property, entity]) => {
      if (entity.mode === 'existing' && entity.id) {
        values[property] = {mode: 'existing', id: entity.id};
      } else if (entity.mode === 'inline' && entity.type && entity.name?.trim()) {
        values[property] = {mode: 'inline', type: entity.type, name: entity.name.trim()};
      }
    });
    controls.storage.value = JSON.stringify(values);
  }

  function refresh(form) {
    updateVisibility(form);
    serialize(form);
  }

  function initialise(form) {
    if (!getControls(form)) return;
    // Always update visibility: the CMS can reuse or replace a form after PJAX.
    updateVisibility(form);
  }

  function initialiseAll() {
    document.querySelectorAll('form').forEach(initialise);
  }

  document.addEventListener('change', event => {
    if (!event.target.matches(typeSelector + ', ' + fieldSelector)) return;
    const form = event.target.closest('form');
    if (form) refresh(form);
  });

  document.addEventListener('input', event => {
    if (!event.target.matches(fieldSelector)) return;
    const form = event.target.closest('form');
    if (form) serialize(form);
  });

  document.addEventListener('submit', event => {
    if (event.target.matches('form')) serialize(event.target);
  }, true);

  // Silverstripe uses jQuery/Chosen and loads edit forms through PJAX.
  // Listen for its jQuery change events as well as native DOM events.
  if (window.jQuery) {
    window.jQuery(document).on('change.schemaAnnotations', typeSelector + ', ' + fieldSelector, function () {
      const form = this.closest('form');
      if (form) refresh(form);
    });
  }

  document.addEventListener('DOMContentLoaded', initialiseAll);
  if (document.readyState !== 'loading') initialiseAll();

  new MutationObserver(records => {
    if (records.some(record => Array.from(record.addedNodes).some(node => node.nodeType === 1))) {
      initialiseAll();
    }
  }).observe(document.documentElement, {childList: true, subtree: true});
})();
