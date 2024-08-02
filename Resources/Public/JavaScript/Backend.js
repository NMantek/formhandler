import DocumentService from '@typo3/core/document-service.js';
import DateTimePicker from '@typo3/backend/date-time-picker.js';
import '@typo3/backend/input/clearable.js';

class FormhandlerBackendModule {
  clearableElements = null;
  dateTimePickerElements = null;
  formhandlerBackendContainer = null;

  constructor() {
    DocumentService.ready().then(() => {
      this.formhandlerBackendContainer = document.querySelector('#formhandler-module');
      this.clearableElements = this.formhandlerBackendContainer.querySelectorAll('.t3js-clearable');
      this.dateTimePickerElements =
        this.formhandlerBackendContainer.querySelectorAll('.t3js-datetimepicker');

      this.initMassExport();
      this.initSelectAllButtons();
      this.initializeClearableElements();
      this.initializeDateTimePickerElements();
    });
  }

  initializeClearableElements() {
    this.clearableElements.forEach((e) => e.clearable());
  }

  initializeDateTimePickerElements() {
    this.dateTimePickerElements.forEach((e) => DateTimePicker.initialize(e));
  }

  initMassExport() {
    const massExportButtons = this.formhandlerBackendContainer.querySelectorAll('.massExport');

    massExportButtons.forEach((exportButton) => {
      const parentTable = exportButton.closest('table');
      const exportUrl = new URL(exportButton.href);

      exportButton.addEventListener('click', (clickEvent) => {
        const selectedCheckboxes = parentTable?.querySelectorAll('.selectCheckbox:checked');
        const selectedCheckboxesIds = Array.prototype.map.call(
          selectedCheckboxes,
          (selectElement) => {
            return selectElement.value;
          },
        );

        if (selectedCheckboxes.length > 0) {
          exportUrl.searchParams.set('logDataUids', selectedCheckboxesIds.join(','));

          exportButton.href = exportUrl.href;
        }
      });
    });
  }

  initSelectAllButtons() {
    /**
     * @type {NodeListOf<HTMLAnchorElement>}
     */
    const checkAllButtons = this.formhandlerBackendContainer.querySelectorAll('.checkAll');

    checkAllButtons.forEach((selectAllElement) => {
      const closestTable = selectAllElement.closest('table');
      const checkboxesOfSameTable = closestTable?.querySelectorAll('.selectCheckbox');

      selectAllElement.addEventListener('click', (clickEvent) => {
        clickEvent.preventDefault();
        const hasCheckCheckbox = closestTable?.querySelectorAll('.selectCheckbox:not(:checked)');

        checkboxesOfSameTable.forEach((checkboxToCheck) => {
          checkboxToCheck.checked = hasCheckCheckbox.length > 0 ? true : false;
        });
      });
    });
  }
}

new FormhandlerBackendModule();
