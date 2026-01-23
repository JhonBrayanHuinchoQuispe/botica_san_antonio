// Validación roja uniforme para formularios de Agregar/Editar en inventario
document.addEventListener('DOMContentLoaded', function() {
  function createErrorElement(message) {
    const el = document.createElement('div');
    el.className = 'field-error';
    el.innerHTML = `<iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>${message}</span>`;
    return el;
  }

  function setupFormValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const fields = form.querySelectorAll('input[required], select[required], textarea[required]');

    fields.forEach(field => {
      const message = field.getAttribute('data-error-message') || 'Este campo es obligatorio';
      let error = field.nextElementSibling && field.nextElementSibling.classList && field.nextElementSibling.classList.contains('field-error')
        ? field.nextElementSibling
        : null;
      if (!error) {
        error = createErrorElement(message);
        field.parentNode.insertBefore(error, field.nextSibling);
      }
      const hideError = () => { error.classList.remove('visible'); field.classList.remove('is-invalid'); };
      const showErrorIfEmpty = () => {
        const empty = (field.tagName.toLowerCase() === 'select') ? !field.value : !field.value.trim();
        error.classList.toggle('visible', empty);
        field.classList.toggle('is-invalid', empty);
      };
      field.addEventListener('input', hideError);
      field.addEventListener('change', hideError);
      field.addEventListener('blur', showErrorIfEmpty);
    });

    form.addEventListener('submit', (e) => {
      let firstInvalid = null;
      const fields = form.querySelectorAll('input[required], select[required], textarea[required]');
      fields.forEach(field => {
        const error = field.nextElementSibling && field.nextElementSibling.classList && field.nextElementSibling.classList.contains('field-error')
          ? field.nextElementSibling
          : null;
        const empty = (field.tagName.toLowerCase() === 'select') ? !field.value : !field.value.trim();
        if (empty) {
          if (error) error.classList.add('visible');
          field.classList.add('is-invalid');
          if (!firstInvalid) firstInvalid = field;
        }
      });
      if (firstInvalid) {
        e.preventDefault();
        firstInvalid.focus();
      }
    });
  }

  setupFormValidation('formEditarProducto');
  setupFormValidation('formAgregarProducto');
});