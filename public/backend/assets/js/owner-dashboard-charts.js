/**
 * Owner dashboard charts — real Booksy data via window.booksyDashboard.
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
    if (!el || typeof flatpickr === 'undefined') {
      return;
    }
    flatpickr('#dashboardDate', {
      wrap: true,
      dateFormat: 'd-M-Y',
      defaultDate: 'today',
    });
  }

  function renderSparkline(selector, data, type) {
    var node = document.querySelector(selector);
    if (!node || typeof ApexCharts === 'undefined' || !data || !data.length) {
      return;
    }

    var options = Object.assign({}, baseChartOptions(60), {
      chart: Object.assign({}, baseChartOptions(60).chart, {
        type: type === 'bar' ? 'bar' : 'line',
        sparkline: { enabled: true },
      }),
      colors: [colors.primary],
      series: [{ name: '', data: data }],
      stroke: type === 'bar' ? undefined : { width: 2, curve: 'smooth' },
      plotOptions:
        type === 'bar'
          ? {
              bar: {
                borderRadius: 2,
                columnWidth: '60%',
              },
            }
          : undefined,
      markers: { size: 0 },
    });

    new ApexCharts(node, options).render();
  }

  function renderRevenueChart() {
    var node = document.querySelector('#revenueChart');
    var daily = payload.charts && payload.charts.daily;
    if (!node || typeof ApexCharts === 'undefined' || !daily) {
      return;
    }

    var options = Object.assign({}, baseChartOptions(400), {
      chart: Object.assign({}, baseChartOptions(400).chart, {
        type: 'area',
      }),
      colors: [colors.primary],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.05,
        },
      },
      grid: {
        padding: { bottom: -4 },
        borderColor: colors.gridBorder,
        xaxis: { lines: { show: true } },
      },
      series: [
        {
          name: labels.appointments || 'Appointments',
          data: daily.total,
        },
      ],
      xaxis: {
        categories: daily.labels,
        labels: {
          rotate: isRtl ? 45 : -45,
          rotateAlways: daily.labels.length > 14,
        },
        axisBorder: { color: colors.gridBorder },
        axisTicks: { color: colors.gridBorder },
      },
      yaxis: {
        labels: {
          formatter: function (val) {
            return Math.round(val);
          },
        },
        title: {
          text: labels.count || 'Count',
          style: { fontSize: '11px', color: colors.muted },
        },
        min: 0,
        forceNiceScale: true,
      },
      stroke: { width: 2, curve: 'smooth' },
      markers: { size: 0 },
    });

    new ApexCharts(node, options).render();
  }

  function renderMonthlyChart() {
    var node = document.querySelector('#monthlySalesChart');
    var monthly = payload.charts && payload.charts.monthly;
    if (!node || typeof ApexCharts === 'undefined' || !monthly) {
      return;
    }

    var options = Object.assign({}, baseChartOptions(318), {
      chart: Object.assign({}, baseChartOptions(318).chart, {
        type: 'bar',
      }),
      colors: [colors.primary],
      fill: { opacity: 0.9 },
      grid: {
        padding: { bottom: -4 },
        borderColor: colors.gridBorder,
        xaxis: { lines: { show: true } },
      },
      series: [
        {
          name: labels.appointments || 'Appointments',
          data: monthly.total,
        },
      ],
      xaxis: {
        categories: monthly.labels,
        axisBorder: { color: colors.gridBorder },
        axisTicks: { color: colors.gridBorder },
      },
      yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
          formatter: function (val) {
            return Math.round(val);
          },
        },
        title: {
          text: labels.count || 'Count',
          style: { fontSize: '11px', color: colors.muted },
        },
      },
      plotOptions: {
        bar: {
          columnWidth: '50%',
          borderRadius: 4,
        },
      },
      stroke: { width: 0 },
    });

    new ApexCharts(node, options).render();
  }

  function renderStatusDonut() {
    var node = document.querySelector('#storageChart');
    var status = payload.charts && payload.charts.status;
    if (!node || typeof ApexCharts === 'undefined' || !status) {
      return;
    }

    if (!status.values.length) {
      node.innerHTML =
        '<p class="text-muted text-center py-5 mb-0">' +
        (labels.noData || 'No appointment data yet.') +
        '</p>';
      return;
    }

    var options = Object.assign({}, baseChartOptions(260), {
      chart: Object.assign({}, baseChartOptions(260).chart, {
        type: 'donut',
      }),
      labels: status.labels,
      series: status.values,
      colors: [colors.primary, colors.success, colors.warning, colors.danger, colors.muted, '#66d1d1'],
      legend: {
        position: 'bottom',
        fontFamily: fontFamily,
      },
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
                  return w.globals.seriesTotals.reduce(function (a, b) {
                    return a + b;
                  }, 0);
                },
              },
            },
          },
        },
      },
    });

    new ApexCharts(node, options).render();
  }

  function init() {
    if (typeof ApexCharts === 'undefined') {
      return;
    }

    initDatePicker();

    var spark = payload.charts && payload.charts.sparkline;
    if (spark) {
      renderSparkline('#customersChart', spark.total, 'line');
      renderSparkline('#ordersChart', spark.pending, 'bar');
      renderSparkline('#growthChart', spark.completed, 'line');
    }

    renderRevenueChart();
    renderMonthlyChart();
    renderStatusDonut();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
