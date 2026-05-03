(function ($) {
  'use strict';

  /** @type {string} Static JSON file next to this page (served over HTTP). */
  var PROPERTIES_JSON_URL = 'BWP_Software_Engineer_Technical_Assessment_properties.json';

  /**
   * In-memory copy of properties returned from {@link loadPropertiesFromJson}.
   * @type {Array<Object>}
   */
  var loadedProperties = [];

  /**
   * Cached jQuery wrappers for DOM nodes used by the table and filters.
   * @type {jQuery}
   */
  var $textFilter;
  var $statusFilter;
  var $tableBody;
  var $resultSummary;
  var $emptyState;
  var $errorBanner;

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
    $errorBanner.text(userVisibleMessage).removeClass('hidden');
    $resultSummary.text('');
  }

  /**
   * Hides the error banner and clears its text.
   * @returns {void}
   */
  function hideErrorBanner() {
    $errorBanner.text('').addClass('hidden');
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
    var searchTextLower = ($textFilter.val() || '').trim().toLowerCase();
    var selectedStatusValue = $statusFilter.val();

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
      $tableBody.empty();
      $emptyState.removeClass('hidden');
    } else {
      $emptyState.addClass('hidden');
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
      $tableBody.html(tableRowsHtml);
    }

    $resultSummary.text(
      'Showing ' +
      filteredProperties.length +
      ' of ' +
      totalLoadedCount +
      ' properties.'
    );
  }

  /**
   * Wires filter controls so any change re-runs {@link renderPropertyTable}.
   * @returns {void}
   */
  function attachFilterEventListeners() {
    $textFilter.on('input', renderPropertyTable);
    $statusFilter.on('change', renderPropertyTable);
  }

  /**
   * Loads {@link PROPERTIES_JSON_URL} via jQuery.ajax (no browser cache),
   * validates JSON shape, stores rows in {@link loadedProperties}, then renders.
   * On failure, shows {@link showLoadOrRuntimeError}.
   * @returns {JQuery.jqXHR|JQuery.Promise}
   */
  function loadPropertiesFromJson() {
    return $.ajax({
      url: PROPERTIES_JSON_URL,
      dataType: 'json',
      cache: false
    })
      .done(function (parsedJsonBody) {
        if (!Array.isArray(parsedJsonBody)) {
          showLoadOrRuntimeError(
            'Could not load properties. Expected a JSON array of properties. If you opened this file directly (file://), serve the folder over HTTP, e.g. "php -S localhost:8000".'
          );
          $tableBody.empty();
          $emptyState.addClass('hidden');
          return;
        }
        loadedProperties = parsedJsonBody;
        hideErrorBanner();
        renderPropertyTable();
      })
      .fail(function (xhr, textStatus, errorThrown) {
        var detail;
        if (textStatus === 'parsererror') {
          detail = 'Invalid JSON response.';
        } else if (xhr && xhr.status) {
          detail = 'HTTP ' + xhr.status + ' ' + (xhr.statusText || '');
        } else {
          detail = String(errorThrown || textStatus || 'Request failed');
        }
        showLoadOrRuntimeError(
          'Could not load properties. ' +
            detail +
            ' If you opened this file directly (file://), serve the folder over HTTP, e.g. "php -S localhost:8000".'
        );
        $tableBody.empty();
        $emptyState.addClass('hidden');
      });
  }

  $(function () {
    $textFilter = $('#textFilter');
    $statusFilter = $('#statusFilter');
    $tableBody = $('#propertiesBody');
    $resultSummary = $('#resultCount');
    $emptyState = $('#emptyState');
    $errorBanner = $('#errorBanner');

    attachFilterEventListeners();
    loadPropertiesFromJson();
  });
})(jQuery);
