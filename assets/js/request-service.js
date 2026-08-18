(function () {
  'use strict';

  const modal = document.getElementById('request-service-modal');
  if (!modal) return;

  const form = document.getElementById('request-service-form');
  const steps = [...modal.querySelectorAll('.request-service-step')];
  const progress = [...modal.querySelectorAll('.request-service-progress span')];

  const back = document.getElementById('request-service-back');
  const next = document.getElementById('request-service-next');
  const submit = document.getElementById('request-service-submit');

  const errorBox = document.getElementById('request-service-error');
  const success = document.getElementById('request-service-success');
  const dialog = modal.querySelector('.request-service-dialog');
  const footer = modal.querySelector('.request-service-footer');

  const fileInput = document.getElementById('rs-files');
  const fileList = document.getElementById('request-service-file-list');

  let selectedFiles = [];

  const residentialType = document.getElementById('rs-residential-type');

  let current = 0;


  /*
   * Show / hide residential-specific fields
   */
  function updateResidentialFields() {

    const selected = form.querySelector(
      'input[name="property_type"]:checked'
    );

    const residential = selected && selected.value === 'Residential';

    if (residential) {

      residentialType.style.display = '';

      form.querySelectorAll(
        'input[name="residence_type"]'
      ).forEach(function (field) {
        field.required = true;
      });

    } else {

      residentialType.style.display = 'none';

      form.querySelectorAll(
        'input[name="residence_type"]'
      ).forEach(function (field) {
        field.required = false;
        field.checked = false;
        field.dataset.wasChecked = 'false';
      });

    }
  }


  /*
   * Open modal
   */
  function openModal() {

    modal.classList.add('is-open');

    document.body.classList.add('request-service-lock');

    current = 0;

    form.reset();

    selectedFiles = [];

    updateFileList();

    modal
      .querySelectorAll(
        '.request-service-option input[type="radio"]'
      )
      .forEach(function (radio) {

        radio.dataset.wasChecked = 'false';

      });

    updateResidentialFields();

    errorBox.style.display = 'none';

    success.classList.remove('is-visible');

    modal.querySelector('.request-service-header').style.display = '';

    footer.style.display = '';

    form.querySelector(
      '.request-service-body'
    ).style.display = '';

    const formContent =
      form.querySelector('.request-service-form-content');

    if (formContent) {
      formContent.style.display = '';
    }

    showStep();
  }


  /*
   * Close modal
   */
  function closeModal() {

    modal.classList.remove('is-open');

    document.body.classList.remove('request-service-lock');

  }


  /*
   * Open modal trigger
   */
  document.addEventListener('click', function (e) {

    const trigger =
      e.target.closest('.request-service-trigger');

    if (!trigger) return;

    e.preventDefault();

    openModal();

  });


  /*
  * Close buttons
  */
  modal
    .querySelectorAll('[data-request-service-close]')
    .forEach(function (el) {

      el.addEventListener('click', closeModal);

    });


  /*
  * Success screen close button
  */
  const successClose = document.getElementById(
    'request-service-success-close'
  );

  if (successClose) {

    successClose.addEventListener('click', function () {

      closeModal();

    });

  }

  /*
   * Radio buttons
   */
  modal
    .querySelectorAll(
      '.request-service-option input[type="radio"]'
    )
    .forEach(function (radio) {

      radio.addEventListener('click', function () {

        if (this.dataset.wasChecked === 'true') {

          this.checked = false;

          this.dataset.wasChecked = 'false';

        } else {

          modal
            .querySelectorAll(
              '.request-service-option input[type="radio"][name="' +
              this.name +
              '"]'
            )
            .forEach(function (other) {

              other.dataset.wasChecked = 'false';

            });

          this.dataset.wasChecked = 'true';

        }

        /*
         * Update Residential Type when
         * Commercial / Residential changes.
         */
        if (this.name === 'property_type') {
          updateResidentialFields();
        }

      });

    });


  /*
   * Escape closes modal
   */
  document.addEventListener('keydown', function (e) {

    if (
      e.key === 'Escape' &&
      modal.classList.contains('is-open')
    ) {

      closeModal();

    }

  });


  /*
   * Show current step
   */
  function showStep() {

    steps.forEach(function (step, i) {

      step.classList.toggle(
        'active',
        i === current
      );

    });


    /*
     * Progress bar
     */
    progress.forEach(function (bar, i) {

      bar.classList.toggle(
        'active',
        i <= current
      );

    });


    /*
     * Back button
     */
    back.style.display =
      current === 0
        ? 'none'
        : 'inline-block';


    /*
     * Next / Submit
     */
    const lastStep =
      current === steps.length - 1;

    next.style.display =
      lastStep
        ? 'none'
        : 'inline-block';

    submit.style.display =
      lastStep
        ? 'inline-block'
        : 'none';


    errorBox.style.display = 'none';

    dialog.scrollTop = 0;

  }


  /*
   * Validate current step
   */
  function validateStep() {

    const fields = steps[current].querySelectorAll(
      'input, select, textarea'
    );

    for (const field of fields) {

      if (!field.checkValidity()) {

        errorBox.textContent = 'Please complete all required fields before continuing.';
        errorBox.style.display = 'block';

        return false;
      }
    }

    errorBox.style.display = 'none';

    return true;
  }


  /*
   * NEXT
   */
  next.addEventListener('click', function () {

    if (!validateStep()) return;

    if (current < steps.length - 1) {

      current++;

      showStep();

    }

  });


  /*
   * BACK
   */
  back.addEventListener('click', function () {

    if (current > 0) {

      current--;

      showStep();

    }

  });

  function updateFileList() {

    fileList.innerHTML = '';

    selectedFiles.forEach(function (file, index) {

      const item = document.createElement('div');

      item.className = 'request-service-file-item';

      const name = document.createElement('span');

      name.className = 'request-service-file-name';

      name.textContent = file.name;


      const remove = document.createElement('button');

      remove.type = 'button';

      remove.className = 'request-service-file-remove';

      remove.setAttribute(
        'aria-label',
        'Remove ' + file.name
      );

      remove.textContent = '×';


      remove.addEventListener('click', function () {

        selectedFiles.splice(index, 1);

        updateFileInput();

        updateFileList();

      });


      item.appendChild(name);

      item.appendChild(remove);

      fileList.appendChild(item);

    });

  }

  function updateFileInput() {

    const dataTransfer = new DataTransfer();

    selectedFiles.forEach(function (file) {

      dataTransfer.items.add(file);

    });

    fileInput.files = dataTransfer.files;

  }

  fileInput.addEventListener('change', function () {

    const newFiles = Array.from(this.files);

    newFiles.forEach(function (file) {

      const alreadyExists = selectedFiles.some(function (existing) {

        return (
          existing.name === file.name &&
          existing.size === file.size &&
          existing.lastModified === file.lastModified
        );

      });

      if (!alreadyExists) {
        selectedFiles.push(file);
      }

    });

    updateFileInput();

    updateFileList();

  });

  /*
   * SUBMIT
   */
  form.addEventListener('submit', async function (e) {

    e.preventDefault();

    if (!validateStep()) {
      return;
    }

    submit.disabled = true;
    next.disabled = true;
    back.disabled = true;

    submit.textContent = 'Sending...';

    errorBox.style.display = 'none';


    try {

      const response = await fetch(
        form.action,
        {
          method: 'POST',
          body: new FormData(form),
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }
      );


      const result = await response.json();


      if (
        !response.ok ||
        !result.success
      ) {

        throw new Error(
          result.message ||
          'Something went wrong. Please try again.'
        );

      }


      /*
      * Show success screen
      */
      form.querySelector('.request-service-form-content').style.display = 'none';

      modal.querySelector('.request-service-header').style.display = 'none';

      footer.style.display = 'none';

      success.classList.add('is-visible');

      modal.classList.add('is-open');

      document.body.classList.add('request-service-lock');


    } catch (err) {

      errorBox.textContent = err.message;

      errorBox.style.display = 'block';


    } finally {

      submit.disabled = false;

      next.disabled = false;

      back.disabled = false;

      submit.textContent = 'Submit Request';

    }

  });


  /*
   * Initial state
   */
  updateResidentialFields();

  showStep();

})();