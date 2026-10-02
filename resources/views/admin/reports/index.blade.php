{{-- G18 通報管理 --}}
<x-layouts.admin title="通報管理" active="reports">
    {{-- ①〜③ 検索条件（初期表示は未対応のみ） --}}
    <div class="mq-panel">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex align-items-end gap-2">
            <div class="mq-field-sm">
                <label class="form-label" for="status">状態</label>
                <select id="status" name="status" class="form-select">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mq-field-md">
                <label class="form-label" for="reason">通報理由</label>
                <select id="reason" name="reason" class="form-select">
                    <option value="">すべて</option>
                    @foreach ($reasons as $item)
                        <option value="{{ $item->value }}" @selected($reason === $item)>{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary px-5">検索</button>
        </form>
    </div>

    {{-- ④ 通報一覧 --}}
    <div class="mq-table-wrap">
        <table class="mq-table">
            <thead>
                <tr>
                    <th>通報日時</th>
                    <th>種別</th>
                    <th>対象の内容</th>
                    <th>通報理由</th>
                    <th>通報者</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    @php
                        $isAnswer = $report->isAboutAnswer();
                        $target = $isAnswer ? $report->answer : $report->question;
                        $content = $isAnswer ? Str::squish($target->body) : $target->title;
                        $targetHidden = ! $target->isPublished() || ($isAnswer && ! $target->question->isPublished());
                    @endphp
                    <tr>
                        <td class="text-nowrap"><x-admin-datetime :at="$report->created_at" /></td>
                        <td>
                            <x-badge :variant="$isAnswer ? 'success' : 'primary'">{{ $isAnswer ? '回答' : '質問' }}</x-badge>
                        </td>
                        <td class="mq-cell-content" title="{{ $content }}">
                            @if ($targetHidden)
                                <x-badge variant="muted" class="me-1">非表示</x-badge>
                            @endif
                            {{ Str::limit($content, 40) }}{{-- 表示幅で数える（全角約20文字） --}}
                        </td>
                        <td>
                            <div class="text-nowrap">{{ $report->reason->label() }}</div>
                            @if ($report->detail)
                                <div class="mq-cell-sub" title="{{ $report->detail }}">{{ Str::limit($report->detail, 60) }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $report->reporter->name }}</td>
                        <td>
                            <x-badge :variant="$report->status === \App\Enums\ReportStatus::Pending ? 'danger' : 'success'">{{ $report->status->label() }}</x-badge>
                        </td>
                        <td class="text-nowrap">
                            @if ($report->status === \App\Enums\ReportStatus::Resolved)
                                <span class="text-body-secondary">対応済み（<x-admin-datetime :at="$report->updated_at" :date-only="true" />）</span>
                            @else
                                {{-- ⑤ 対象を非表示（確認モーダルを開く。対象が既に非表示なら出さない） --}}
                                @unless ($targetHidden)
                                    <button type="button" class="btn btn-danger mq-btn-xs"
                                            data-bs-toggle="modal" data-bs-target="#hide-target-modal"
                                            data-confirm-action="{{ route('admin.reports.hide-target', $report) }}"
                                            data-confirm-message="{{ ($isAnswer ? '回答：' : '質問：').Str::limit($content, 80) }}">対象を非表示</button>
                                @endunless
                                {{-- ⑥ 対応済みにする --}}
                                <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="d-inline" data-submit-once>
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary mq-btn-xs">対応済みにする</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-body-secondary py-4">該当する通報はありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ⑦ ページネーション（20件ごと） --}}
    {{ $reports->links('pagination.admin') }}

    <x-confirm-modal id="hide-target-modal" title="対象の投稿を非表示にしますか？"
                     description="次の投稿を非表示にし、この投稿への未対応の通報をすべて対応済みにします。"
                     confirm-label="非表示にする" />
</x-layouts.admin>
