import Chart from 'chart.js/auto';

/**
 * Grafik halaman Laporan. Setiap <canvas data-chart='{"type":..., "labels":[...], "datasets":[...]}'>
 * dirender otomatis. Warna dataset memakai slot palet di bawah (urutan tetap, tidak diputar),
 * atau warna status untuk dataset yang menyebut "status". Tipe "doughnut" memakai warna per
 * irisan dari dataset.colors; opsi "trend" menambah garis tren linear di atas dataset pertama.
 */
const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100'];

const STATUS = {
    good: '#0ca30c',
    warning: '#fab219',
    critical: '#d03b3b',
    neutral: '#c3c2b7',
};

const INK = {
    secondary: '#52514e',
    muted: '#898781',
    grid: '#e1e0d9',
    baseline: '#c3c2b7',
    surface: '#ffffff',
};

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 12;
Chart.defaults.color = INK.secondary;

const numberFormat = new Intl.NumberFormat('id-ID');

function datasetStyle(config, dataset, index) {
    const color = dataset.status ? STATUS[dataset.status] : SERIES[index % SERIES.length];
    const isLine = config.type === 'line';
    const stacked = Boolean(config.stacked);

    if (isLine) {
        return {
            borderColor: color,
            backgroundColor: color,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: color,
            pointBorderColor: INK.surface,
            pointBorderWidth: 2,
            tension: 0.25,
        };
    }

    return {
        backgroundColor: color,
        hoverBackgroundColor: color,
        // Celah 2px warna permukaan di antara segmen dan bar yang berdampingan.
        borderColor: INK.surface,
        borderWidth: stacked ? { top: 0, bottom: 0, left: 0, right: 0, [config.horizontal ? 'right' : 'top']: 2 } : 0,
        borderSkipped: 'start',
        borderRadius: stacked ? 0 : 4,
        maxBarThickness: config.horizontal ? 22 : 36,
        categoryPercentage: 0.7,
        barPercentage: 0.9,
    };
}

function tooltipStyle(label) {
    return {
        backgroundColor: '#0b0b0b',
        padding: 10,
        cornerRadius: 6,
        boxPadding: 4,
        callbacks: { label },
    };
}

function renderDoughnut(canvas, config) {
    const dataset = config.datasets[0];
    const total = dataset.data.reduce((sum, value) => sum + value, 0);

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: config.labels,
            datasets: [{
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.colors ?? SERIES,
                hoverBackgroundColor: dataset.colors ?? SERIES,
                // Celah 2px warna permukaan di antara irisan.
                borderColor: INK.surface,
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: tooltipStyle((item) => {
                    const percent = total > 0 ? Math.round(item.parsed / total * 100) : 0;

                    return `${item.label}: ${numberFormat.format(item.parsed)} ${config.unit ?? ''} (${percent}%)`;
                }),
            },
        },
    });
}

/** Garis tren regresi linear (kuadrat terkecil) dari deret nilai. */
function linearTrend(values) {
    const n = values.length;
    const meanX = (n - 1) / 2;
    const meanY = values.reduce((sum, value) => sum + value, 0) / n;
    let numerator = 0;
    let denominator = 0;

    values.forEach((value, x) => {
        numerator += (x - meanX) * (value - meanY);
        denominator += (x - meanX) ** 2;
    });

    const slope = denominator ? numerator / denominator : 0;

    return values.map((_, x) => Math.round((meanY + slope * (x - meanX)) * 10) / 10);
}

function trendDataset(values) {
    return {
        type: 'line',
        label: 'Tren',
        data: linearTrend(values),
        borderColor: INK.secondary,
        backgroundColor: INK.secondary,
        borderWidth: 2,
        borderDash: [6, 4],
        pointRadius: 0,
        pointHoverRadius: 0,
        pointStyle: 'line',
        tension: 0,
        order: 0,
    };
}

function render(canvas) {
    const config = JSON.parse(canvas.dataset.chart);

    if (config.type === 'doughnut') {
        return renderDoughnut(canvas, config);
    }

    const horizontal = Boolean(config.horizontal);
    const withTrend = Boolean(config.trend) && config.datasets[0].data.length > 1;
    const multiSeries = config.datasets.length > 1 || withTrend;

    const valueAxis = {
        beginAtZero: true,
        stacked: Boolean(config.stacked),
        grid: { color: INK.grid, drawTicks: false },
        border: { display: false },
        ticks: { color: INK.muted, precision: 0, padding: 8 },
    };

    const categoryAxis = {
        stacked: Boolean(config.stacked),
        grid: { display: false },
        border: { color: INK.baseline },
        ticks: { color: INK.secondary, padding: 6, autoSkip: ! horizontal },
    };

    new Chart(canvas, {
        type: config.type === 'line' ? 'line' : 'bar',
        data: {
            labels: config.labels,
            datasets: [
                ...config.datasets.map((dataset, index) => ({
                    label: dataset.label,
                    data: dataset.data,
                    order: 1,
                    ...(withTrend ? { pointStyle: 'rectRounded' } : {}),
                    ...datasetStyle(config, dataset, index),
                })),
                ...(withTrend ? [trendDataset(config.datasets[0].data)] : []),
            ],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: horizontal
                ? { x: valueAxis, y: categoryAxis }
                : { x: categoryAxis, y: valueAxis },
            plugins: {
                legend: {
                    display: multiSeries,
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        useBorderRadius: true,
                        borderRadius: 2,
                        color: INK.secondary,
                        padding: 16,
                        // Garis tren tampil sebagai garis di legenda dan selalu di urutan terakhir.
                        usePointStyle: withTrend,
                        sort: (a, b) => a.datasetIndex - b.datasetIndex,
                    },
                },
                tooltip: tooltipStyle(
                    (item) => `${item.dataset.label}: ${numberFormat.format(item.parsed[horizontal ? 'x' : 'y'])} ${config.unit ?? ''}`.trim(),
                ),
            },
        },
    });
}

document.querySelectorAll('canvas[data-chart]').forEach(render);
