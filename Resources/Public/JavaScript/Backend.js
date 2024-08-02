import DocumentService from '@typo3/core/document-service.js';
import DateTimePicker from '@typo3/backend/date-time-picker.js';
import '@typo3/backend/input/clearable.js';

const formhandlerBackendContainer = document.querySelector('#formhandler-module');

class FormhandlerBackendModule {
  clearableElements = null;
  dateTimePickerElements = null;

  constructor() {
    DocumentService.ready().then(() => {
      this.clearableElements = document.querySelectorAll('.t3js-clearable');
      this.dateTimePickerElements = document.querySelectorAll('.t3js-datetimepicker');
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
    /**
     * @type {NodeListOf<HTMLAnchorElement>}
     */
    const massExportButtons = formhandlerBackendContainer.querySelectorAll('.massExport');

    massExportButtons.forEach((exportButton) => {
      const parentTable = exportButton.closest('table');
      const exportUrl = new URL(exportButton.href);

      exportButton.addEventListener('click', (clickEvent) => {
        const selectedCheckboxes = parentTable?.querySelectorAll('.selectCheckbox:checked');
        /**
         * @type {Array<string>}
         */
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
    const checkAllButtons = formhandlerBackendContainer.querySelectorAll('.checkAll');

    checkAllButtons.forEach((selectAllElement) => {
      const closestTable = selectAllElement.closest('table');
      /**
       * @type {NodeListOf<HTMLInputElement>}
       */
      const checkboxesOfSameTable = closestTable?.querySelectorAll('.selectCheckbox');

      selectAllElement.addEventListener('click', (clickEvent) => {
        clickEvent.preventDefault();

        checkboxesOfSameTable.forEach((checkboxToCheck) => {
          checkboxToCheck.checked = !checkboxToCheck.checked;
        });
      });
    });
  }
}

new FormhandlerBackendModule();
