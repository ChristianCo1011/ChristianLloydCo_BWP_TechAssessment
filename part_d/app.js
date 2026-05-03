(function () {
  'use strict';

  /** @type {string} Static JSON file next to this page (served over HTTP). */
  var PROPERTIES_JSON_URL = 'BWP_Software_Engineer_Technical_Assessment_properties.json';

  /**
   * In-memory copy of properties returned from {@link loadPropertiesFromJson}.
   * @type {Array<Object>}
   */
  var loadedProperties = [];

  /**
   * Cached references to DOM nodes used by the table and filters.
   * @type {{
   *   textFilterInput: HTMLInputElement,
   *   statusFilterSelect: HTMLSelectElement,
   *   tableBody: HTMLTableSectionElement,
   *   resultSummary: HTMLElement,
   *   emptyState: HTMLElement,
   *   errorBanner: HTMLElement
   * }}
   */
  var dom = {
    textFilterInput: document.getElementById('textFilter'),
    statusFilterSelect: document.getElementById('statusFilter'),
    tableBody: document.getElementById('propertiesBody'),
    resultSummary: document.getElementById('resultCount'),
    emptyState: document.getElementById('emptyState'),
    errorBanner: document.getElementById('errorBanner')
  };

  /** @type {Intl.NumberFormat} Formats numeric `price` for table cells. */
  var priceDisplayFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
  });

  /**
   * Shows the error banner and clears the result summary line.
   * @param {string} userVisibleMessage Full message to show the user.
   * @returns {void}
   */
  function showLoadOrRuntimeError(userVisibleMessage) {
    dom.errorBanner.textContent = userVisibleMessage;
    dom.errorBanner.classList.remove('hidden');
    dom.resultSummary.textContent = '';
  }

  /**
   * Hides the error banner and clears its text.
   * @returns {void}
   */
  function hideErrorBanner() {
    dom.errorBanner.textContent = '';
    dom.errorBanner.classList.add('hidden');
  }

  /**
   * Escapes a string so it is safe to inject into HTML text nodes.
   * @param {unknown} rawValue Value from JSON or user input.
   * @returns {string} HTML-safe text (entities escaped).
   */
  function escapeHtmlForTextNode(rawValue) {
    return String(rawValue == null ? '' : rawValue)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * Returns properties that match the current text and status filters.
   * Text filter is case-insensitive and matches `label` or `project_code`.
   * @returns {Array<Object>} Subset of {@link loadedProperties}.
   */
  function getFilteredProperties() {
    var searchTextLower = (dom.textFilterInput.value || '').trim().toLowerCase();
    var selectedStatusValue = dom.statusFilterSelect.value;

    return loadedProperties.filter(function (propertyRow) {
      if (selectedStatusValue !== 'all' && propertyRow.status !== selectedStatusValue) {
        return false;
      }
      if (searchTextLower.length === 0) {
        return true;
      }
      var labelLower = (propertyRow.label || '').toLowerCase();
      var projectCodeLower = (propertyRow.project_code || '').toLowerCase();
      return (
        labelLower.indexOf(searchTextLower) !== -1 ||
        projectCodeLower.indexOf(searchTextLower) !== -1
      );
    });
  }

  /**
   * Rebuilds the table body and toggles empty state from {@link loadedProperties}
   * and current filter values.
   * @returns {void}
   */
  function renderPropertyTable() {
    var filteredProperties = getFilteredProperties();
    var totalLoadedCount = loadedProperties.length;

    if (filteredProperties.length === 0) {
      dom.tableBody.innerHTML = '';
      dom.emptyState.classList.remove('hidden');
    } else {
      dom.emptyState.classList.add('hidden');
      var tableRowsHtml = filteredProperties
        .map(function (propertyRow) {
          var priceCellHtml;
          if (propertyRow.price == null || isNaN(propertyRow.price)) {
            priceCellHtml = '&mdash;';
          } else {
            priceCellHtml = priceDisplayFormatter.format(Number(propertyRow.price));
          }
          var statusCssClass = escapeHtmlForTextNode(propertyRow.status || '');
          return (
            '<tr>' +
              '<td>' + escapeHtmlForTextNode(propertyRow.label) + '</td>' +
              '<td><span class="badge ' +
              statusCssClass +
              '">' +
              escapeHtmlForTextNode(propertyRow.status) +
              '</span></td>' +
              '<td class="price">' +
              priceCellHtml +
              '</td>' +
              '<td>' +
              escapeHtmlForTextNode(propertyRow.project_code) +
              '</td>' +
            '</tr>'
          );
        })
        .join('');
      dom.tableBody.innerHTML = tableRowsHtml;
    }

    dom.resultSummary.textContent =
      'Showing ' +
      filteredProperties.length +
      ' of ' +
      totalLoadedCount +
      ' properties.';
  }

  /**
   * Wires filter controls so any change re-runs {@link renderPropertyTable}.
   * @returns {void}
   */
  function attachFilterEventListeners() {
    dom.textFilterInput.addEventListener('input', renderPropertyTable);
    dom.statusFilterSelect.addEventListener('change', renderPropertyTable);
  }

  /**
   * Fetches {@link PROPERTIES_JSON_URL}, validates JSON shape, stores rows in
   * {@link loadedProperties}, then renders. On failure, shows {@link showLoadOrRuntimeError}.
   * @returns {Promise<void>}
   */
  function loadPropertiesFromJson() {
    return fetch(PROPERTIES_JSON_URL, { cache: 'no-store' })
      .then(function (httpResponse) {
        if (!httpResponse.ok) {
          throw new Error('HTTP ' + httpResponse.status + ' ' + httpResponse.statusText);
        }
        return httpResponse.json();
      })
      .then(function (parsedJsonBody) {
        if (!Array.isArray(parsedJsonBody)) {
          throw new Error('Expected a JSON array of properties.');
        }
        loadedProperties = parsedJsonBody;
        hideErrorBanner();
        renderPropertyTable();
      })
      .catch(function (fetchOrParseError) {
        showLoadOrRuntimeError(
          'Could not load properties. ' +
            fetchOrParseError.message +
            ' If you opened this file directly (file://), serve the folder over HTTP, e.g. "php -S localhost:8000".'
        );
        dom.tableBody.innerHTML = '';
        dom.emptyState.classList.add('hidden');
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    attachFilterEventListeners();
    loadPropertiesFromJson();
  });
})();
