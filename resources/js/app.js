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

    // 入力エラーで戻ってきたときにボトムシートを開き直す：data-mq-autoshow
    document.querySelectorAll('.offcanvas[data-mq-autoshow]').forEach((sheet) => {
        bootstrap.Offcanvas.getOrCreateInstance(sheet).show();
    });
});
