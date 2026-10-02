import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    // 削除などの確認ダイアログ：<form data-confirm="削除しますか？">
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // 二重送信の防止：<form data-submit-once>（送信後はボタンを押せなくする）
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        });
    });

    // G9 星評価：選んだ数を「評価：n / 5」に表示し、選ぶまで「決定する」を押せなくする
    document.querySelectorAll('[data-rating]').forEach((fieldset) => {
        const form = fieldset.closest('form');
        const value = fieldset.querySelector('[data-rating-value]');
        const submit = form.querySelector('[data-rating-submit]');
        const update = () => {
            const checked = fieldset.querySelector('input[name="rating"]:checked');
            value.textContent = `評価：${checked ? checked.value : '−'} / 5`;
            submit.disabled = !checked;
        };
        fieldset.addEventListener('change', update);
        update();
    });

    // 確認モーダル：開いたボタンの data-confirm-action を送信先に、data-confirm-message を本文に入れる
    document.querySelectorAll('[data-confirm-modal]').forEach((modal) => {
        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }
            modal.querySelector('[data-confirm-form]').action = trigger.dataset.confirmAction;
            modal.querySelector('[data-confirm-target]').textContent = trigger.dataset.confirmMessage || '';
            modal.querySelector('[data-confirm-submit]').disabled = false;
        });
        modal.querySelector('[data-confirm-form]').addEventListener('submit', (event) => {
            event.submitter?.setAttribute('disabled', 'disabled');
        });
    });

    // G16 行内編集：編集／キャンセルで行の表示と入力欄を切り替える
    document.querySelectorAll('[data-edit-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-edit-row]');
            row.classList.toggle('is-editing');
            if (row.classList.contains('is-editing')) {
                row.querySelector('.mq-edit-row__field[name="name"]')?.focus();
            }
        });
    });

    // G13 グラフ（Chart.js）：<canvas data-chart='{"type":"line|bar","labels":[...],"data":[...]}'>
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        renderChart(canvas, JSON.parse(canvas.dataset.chart));
    });

    // 入力エラーで戻ってきたときにボトムシートを開き直す：data-mq-autoshow
    document.querySelectorAll('.offcanvas[data-mq-autoshow]').forEach((sheet) => {
        bootstrap.Offcanvas.getOrCreateInstance(sheet).show();
    });
});

const CHART_PRIMARY = '#2e70d9';
const CHART_GRID = '#d9dee8';
const CHART_TEXT = '#6b7587';

// 横棒の右に値を描く（設計画像の「420」などの数値ラベル。追加パッケージは使わない）
const barValueLabels = {
    id: 'mqBarValueLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.fillStyle = CHART_TEXT;
        ctx.font = '12px system-ui, sans-serif';
        ctx.textBaseline = 'middle';
        chart.getDatasetMeta(0).data.forEach((bar, index) => {
            ctx.fillText(String(chart.data.datasets[0].data[index]), bar.x + 8, bar.y);
        });
        ctx.restore();
    },
};

function renderChart(canvas, { type, labels, data }) {
    const common = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
    };

    if (type === 'line') {
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data,
                    borderColor: CHART_PRIMARY,
                    backgroundColor: '#ffffff',
                    pointBorderColor: CHART_PRIMARY,
                    pointBackgroundColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0,
                }],
            },
            options: {
                ...common,
                scales: {
                    x: { grid: { display: false }, ticks: { color: CHART_TEXT } },
                    y: { beginAtZero: true, grid: { color: CHART_GRID }, border: { display: false }, ticks: { color: CHART_TEXT, precision: 0 } },
                },
            },
        });
    }

    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets: [{ data, backgroundColor: CHART_PRIMARY, barThickness: 18, borderRadius: 2 }] },
        options: {
            ...common,
            indexAxis: 'y',
            layout: { padding: { right: 40 } },
            scales: {
                x: { display: false, beginAtZero: true },
                y: { grid: { display: false }, border: { display: false }, ticks: { color: '#212633' } },
            },
        },
        plugins: [barValueLabels],
    });
}
