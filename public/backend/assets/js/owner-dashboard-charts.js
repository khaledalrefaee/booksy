/**
 * Owner / Company dashboard charts — real Booksy data via window.booksyDashboard.
 */
(function () {
  'use strict';

  var payload = window.booksyDashboard || {};
  var isDark = payload.theme !== 'light';
  var isRtl = payload.rtl === true;

  var colors = isDark
    ? {
        primary: '#6571ff',
        success: '#05a34a',
        warning: '#fbbc06',
        danger: '#ff3366',
        muted: '#7987a1',
        gridBorder: 'rgba(77, 138, 240, .15)',
        bodyColor: '#b8c3d9',
        cardBg: '#0c1427',
      }
    : {
        primary: '#6571ff',
        success: '#05a34a',
        warning: '#fbbc06',
        danger: '#ff3366',
        muted: '#7987a1',
        gridBorder: 'rgba(0, 0, 0, .08)',
        bodyColor: '#212529',
        cardBg: '#ffffff',
      };

  var chartTheme = isDark ? 'dark' : 'light';
  var fontFamily = "'Roboto', Helvetica, sans-serif";
  var labels = payload.labels || {};

  function baseChartOptions(height) {
    return {
      chart: {
        parentHeightOffset: 0,
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: { show: false },
        height: height,
      },
      theme: { mode: chartTheme },
      tooltip: { theme: chartTheme },
      dataLabels: { enabled: false },
    };
  }

  function initDatePicker() {
    var el = document.getElementById('dashboardDate');
    if (!el || typeof flatpickr === 'undefined') return;
    flatpickr('#dashboardDate', {
      wrap: true,
      dateFormat: 'd-M-Y',
      defaultDate: 'today',
    });
  }

  /* ── Sparklines (stat cards) ── */
  function renderSparkline(selector, data, type) {
    var node = document.querySelector(selector);
    if (!node || typeof ApexCharts === 'undefined' || !data || !data.length) return;

    var base = baseChartOptions(60);
    var options = {
      chart: Object.assign({}, base.chart, {
        type: type === 'bar' ? 'bar' : 'line',
        sparkline: { enabled: true },
      }),
      theme: base.theme,
      tooltip: base.tooltip,
      dataLabels: { enabled: false },
      colors: [colors.primary],
      series: [{ name: '', data: data }],
      stroke: type === 'bar' ? undefined : { width: 2, curve: 'smooth' },
      plotOptions: type === 'bar' ? { bar: { borderRadius: 2, columnWidth: '60%' } } : undefined,
      markers: { size: 0 },
    };

    new ApexCharts(node, options).render();
  }

  /* ── Daily appointments chart (#revenueChart) ── */
  function renderRevenueChart() {
    var node = document.querySelector('#revenueChart');
    if (!node || typeof ApexCharts === 'undefined') return;

    var daily = payload.charts && payload.charts.daily;
    if (!daily) return;

    var base = baseChartOptions(350);
    var options = {
      chart: Object.assign({}, base.chart, { type: 'area' }),
      theme: base.theme,
      tooltip: Object.assign({}, base.tooltip, {
        y: {
          formatter: function (val) {
            return Math.round(val) + ' ' + (labels.appointments || 'Appointments');
          },
        },
      }),
      dataLabels: { enabled: false },
      colors: [colors.primary],
      fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 },
      },
      grid: {
        padding: { bottom: -4 },
        borderColor: colors.gridBorder,
        xaxis: { lines: { show: false } },
      },
      series: [{ name: labels.appointments || 'Appointments', data: daily.total }],
      xaxis: {
        categories: daily.labels,
        labels: {
          rotate: isRtl ? 45 : -45,
          rotateAlways: daily.labels.length > 8,
          style: { fontSize: '11px' },
        },
        axisBorder: { color: colors.gridBorder },
        axisTicks: { color: colors.gridBorder },
      },
      yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
          formatter: function (val) { return Math.round(val); },
        },
        title: { text: labels.count || 'Count', style: { fontSize: '11px', color: colors.muted } },
      },
      stroke: { width: 2, curve: 'smooth' },
      markers: { size: 0 },
    };

    new ApexCharts(node, options).render();
  }

  /* ── Monthly appointments bar chart (#monthlySalesChart or #appointmentsChart) ── */
  function renderMonthlyChart() {
    /* Use whichever monthly-appointments node exists */
    var selector = document.querySelector('#appointmentsChart') ? '#appointmentsChart' : '#monthlySalesChart';
    var node = document.querySelector(selector);
    if (!node || typeof ApexCharts === 'undefined') return;

    var monthly = payload.charts && payload.charts.monthly;
    if (!monthly) return;

    var base = baseChartOptions(300);
    var options = {
      chart: Object.assign({}, base.chart, { type: 'bar' }),
      theme: base.theme,
      tooltip: Object.assign({}, base.tooltip, {
        y: {
          formatter: function (val) {
            return Math.round(val) + ' ' + (labels.appointments || 'Appointments');
          },
        },
      }),
      dataLabels: { enabled: false },
      colors: [colors.primary],
      fill: { opacity: 0.9 },
      grid: {
        padding: { bottom: -4 },
        borderColor: colors.gridBorder,
        xaxis: { lines: { show: false } },
      },
      series: [{ name: labels.appointments || 'Appointments', data: monthly.total }],
      xaxis: {
        categories: monthly.labels,
        labels: {
          rotate: isRtl ? 45 : -45,
          rotateAlways: monthly.labels.length > 8,
          style: { fontSize: '11px' },
        },
        axisBorder: { color: colors.gridBorder },
        axisTicks: { color: colors.gridBorder },
      },
      yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
          formatter: function (val) { return Math.round(val); },
        },
        title: { text: labels.count || 'Count', style: { fontSize: '11px', color: colors.muted } },
      },
      plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
      stroke: { width: 0 },
    };

    new ApexCharts(node, options).render();
  }

  /* ── Status donut (#storageChart) ── */
  function renderStatusDonut() {
    var node = document.querySelector('#storageChart');
    if (!node || typeof ApexCharts === 'undefined') return;

    var status = payload.charts && payload.charts.status;
    if (!status) return;

    if (!status.values || !status.values.length) {
      node.innerHTML =
        '<p class="text-muted text-center py-5 mb-0">' +
        (labels.noData || 'No appointment data yet.') +
        '</p>';
      return;
    }

    var base = baseChartOptions(260);
    var options = {
      chart: Object.assign({}, base.chart, { type: 'donut' }),
      theme: base.theme,
      tooltip: base.tooltip,
      dataLabels: { enabled: false },
      labels: status.labels,
      series: status.values,
      colors: [colors.primary, colors.success, colors.warning, colors.danger, colors.muted, '#66d1d1'],
      legend: { position: 'bottom', fontFamily: fontFamily },
      plotOptions: {
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: true,
              total: {
                show: true,
                label: labels.total || 'Total',
                color: colors.bodyColor,
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                },
              },
            },
          },
        },
      },
    };

    new ApexCharts(node, options).render();
  }

  /* ── Init ── */
  function init() {
    if (typeof ApexCharts === 'undefined') return;

    initDatePicker();

    var spark = payload.charts && payload.charts.sparkline;
    if (spark) {
      renderSparkline('#customersChart', spark.total, 'line');
      renderSparkline('#ordersChart', spark.pending, 'bar');
      renderSparkline('#growthChart', spark.completed, 'line');
    }

    renderMonthlyChart();
    renderRevenueChart();
    renderStatusDonut();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
