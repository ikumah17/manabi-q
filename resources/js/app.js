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

    // 入力エラーで戻ってきたときにボトムシートを開き直す：data-mq-autoshow
    document.querySelectorAll('.offcanvas[data-mq-autoshow]').forEach((sheet) => {
        bootstrap.Offcanvas.getOrCreateInstance(sheet).show();
    });
});
