{{-- G13 管理者ダッシュボード --}}
@php
    $cards = [
        ['label' => '総ユーザー数', 'value' => $summary['users'], 'unit' => '人', 'alert' => false],
        ['label' => '総質問数', 'value' => $summary['questions'], 'unit' => '件', 'alert' => false],
        ['label' => '総回答数', 'value' => $summary['answers'], 'unit' => '件', 'alert' => false],
        ['label' => '未解決の質問', 'value' => $summary['unresolved'], 'unit' => '件', 'alert' => true],
        ['label' => '未対応の通報', 'value' => $summary['pendingReports'], 'unit' => '件', 'alert' => true],
    ];
    $lineChart = ['type' => 'line', 'labels' => $monthly->pluck('label'), 'data' => $monthly->pluck('count')];
    $barChart = ['type' => 'bar', 'labels' => $byCategory->pluck('label'), 'data' => $byCategory->pluck('count')];
@endphp
<x-layouts.admin title="ダッシュボード" active="dashboard">
    {{-- ② 集計サマリー --}}
    <section class="mq-stat-cards mb-3" aria-label="集計サマリー">
        @foreach ($cards as $card)
            <div class="mq-stat-card">
                <div class="mq-stat-card__label">{{ $card['label'] }}</div>
                <div @class(['mq-stat-card__value', 'is-alert' => $card['alert']])>
                    {{ number_format($card['value']) }}<span class="mq-stat-card__unit">{{ $card['unit'] }}</span>
                </div>
            </div>
        @endforeach
    </section>

    <div class="row g-3 mb-3">
        {{-- ③ 質問投稿数の推移（月別・直近7か月） --}}
        <section class="col-7">
            <div class="mq-panel h-100 mb-0">
                <h2 class="mq-panel-title" id="chart-monthly-title">質問投稿数の推移（月別）</h2>
                <div class="mq-chart mq-chart--line">
                    <canvas data-chart='@json($lineChart)' role="img" aria-labelledby="chart-monthly-title"></canvas>
                </div>
                <table class="visually-hidden">
                    <caption>質問投稿数の推移（月別）</caption>
                    @foreach ($monthly as $row)
                        <tr><th scope="row">{{ $row['start'] }}</th><td>{{ $row['count'] }}件</td></tr>
                    @endforeach
                </table>
            </div>
        </section>

        {{-- ④ カテゴリ別質問数 --}}
        <section class="col-5">
            <div class="mq-panel mb-0">
                <h2 class="mq-panel-title" id="chart-category-title">カテゴリ別質問数</h2>
                <div class="mq-chart" style="height: {{ max(120, $byCategory->count() * 30 + 20) }}px">
                    <canvas data-chart='@json($barChart)' role="img" aria-labelledby="chart-category-title"></canvas>
                </div>
                <table class="visually-hidden">
                    <caption>カテゴリ別質問数</caption>
                    @foreach ($byCategory as $row)
                        <tr><th scope="row">{{ $row['label'] }}</th><td>{{ $row['count'] }}件</td></tr>
                    @endforeach
                </table>
            </div>
        </section>
    </div>

    {{-- ⑤ 最近の投稿（新着5件。非表示の投稿にはバッジを付ける） --}}
    <section class="mq-panel">
        <h2 class="mq-panel-title">最近の投稿</h2>
        @forelse ($recentPosts as $post)
            <div class="mq-recent-post">
                <x-badge :variant="$post['type'] === 'question' ? 'primary' : 'success'">{{ $post['type'] === 'question' ? '質問' : '回答' }}</x-badge>
                @if ($post['hidden'])
                    <x-badge variant="muted">非表示</x-badge>
                @endif
                <span class="mq-recent-post__content">{{ $post['content'] }}</span>
                <span class="mq-recent-post__meta">{{ $post['user'] }}</span>
                <time class="mq-recent-post__meta" datetime="{{ $post['created_at']->toIso8601String() }}">{{ $post['created_at']->format('Y/m/d H:i') }}</time>
            </div>
        @empty
            <p class="text-body-secondary mb-0">まだ投稿はありません</p>
        @endforelse
    </section>
</x-layouts.admin>
