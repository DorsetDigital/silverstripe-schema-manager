(function () {
  function sync(form) {
    const type = form.querySelector('[name="SchemaAnnotationType"]');
    const target = form.querySelector('[name="SchemaAnnotationValues"]');
    if (!type || !target) return;
    const values = {}, entities = {};
    form.querySelectorAll('[data-schema-type]').forEach(field => {
      const active = field.dataset.schemaType === type.value;
      const wrapper = field.closest('.field');
      if (wrapper) wrapper.hidden = !active;
      if (!active) return;
      const key = field.dataset.schemaProperty;
      const part = field.dataset.schemaEntityPart;
      if (part) {
        (entities[key] ||= {})[part] = field.value;
      } else if (field.value !== '') {
        values[key] = field.value;
      }
    });
    Object.entries(entities).forEach(([key, entity]) => {
      if (entity.mode === 'existing' && entity.id) values[key] = {mode:'existing', id:entity.id};
      if (entity.mode === 'inline' && entity.type && entity.name?.trim()) {
        values[key] = {mode:'inline', type:entity.type, name:entity.name.trim()};
      }
    });
    target.value = JSON.stringify(values);
  }
  document.addEventListener('change', e => {
    if (e.target.matches('[name="SchemaAnnotationType"], [data-schema-type]')) {
      const form = e.target.closest('form');
      if (form) sync(form);
    }
  });
  document.addEventListener('input', e => {
    if (e.target.matches('[data-schema-type]')) {
      const form = e.target.closest('form');
      if (form) sync(form);
    }
  });
  document.addEventListener('submit', e => sync(e.target), true);
})();
