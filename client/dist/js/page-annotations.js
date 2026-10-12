(function () {
  'use strict';

  const fieldSelector = '[data-schema-type]';
  const typeSelector = '[name="SchemaAnnotationType"]';
  const valuesSelector = '[name="SchemaAnnotationValues"]';

  function wrapper(field) {
    return field.closest('.field, .form-group, .schema-annotation-help') || field;
  }

  function controls(form) {
    const type = form.querySelector(typeSelector);
    const storage = form.querySelector(valuesSelector);
    return type && storage ? {type, storage} : null;
  }

  function setVisibility(form) {
    const state = controls(form);
    if (!state) return;
    form.querySelectorAll(fieldSelector).forEach(field => {
      const active = field.dataset.schemaType === state.type.value;
      const part = field.dataset.schemaEntityPart;
      let visible = active;
      if (active && part && part !== 'mode') {
        const prefix = field.name.replace(/_(id|type|name)$/, '');
        const mode = Array.from(form.querySelectorAll('[data-schema-entity-part="mode"]'))
          .find(candidate => candidate.name === prefix + '_mode');
        visible = mode && mode.value === (part === 'id' ? 'existing' : 'inline');
      }
      wrapper(field).hidden = !visible;
    });
  }

  function serialize(form) {
    const state = controls(form);
    if (!state) return;
    const values = {};
    const entities = {};
    form.querySelectorAll(fieldSelector).forEach(field => {
      if (field.dataset.schemaType !== state.type.value) return;
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
      } else if (entity.mode === 'inline' && entity.type && entity.name && entity.name.trim()) {
        values[property] = {mode: 'inline', type: entity.type, name: entity.name.trim()};
      }
    });
    state.storage.value = JSON.stringify(values);
  }

  function update(form) {
    setVisibility(form);
    serialize(form);
  }

  function initialise(form) {
    if (!controls(form) || form.dataset.schemaAnnotationInitialised === '1') return;
    form.dataset.schemaAnnotationInitialised = '1';
    update(form);
  }

  function initialiseAll() {
    document.querySelectorAll('form').forEach(initialise);
  }

  document.addEventListener('change', event => {
    if (!event.target.matches(typeSelector + ', ' + fieldSelector)) return;
    const form = event.target.closest('form');
    if (form) update(form);
  });

  document.addEventListener('input', event => {
    if (!event.target.matches(fieldSelector)) return;
    const form = event.target.closest('form');
    if (form) serialize(form);
  });

  document.addEventListener('submit', event => {
    if (event.target.matches('form')) serialize(event.target);
  }, true);

  document.addEventListener('DOMContentLoaded', initialiseAll);
  if (document.readyState !== 'loading') initialiseAll();

  const observer = new MutationObserver(records => {
    if (records.some(record => Array.from(record.addedNodes).some(node => node.nodeType === 1))) {
      initialiseAll();
    }
  });
  observer.observe(document.documentElement, {childList: true, subtree: true});
})();
