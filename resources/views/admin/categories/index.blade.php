{{-- G16 カテゴリ管理 --}}
@php
    $editErrors = $errors->getBag('edit');
    $editingId = $editErrors->isNotEmpty() ? (int) old('editing') : null;
@endphp
<x-layouts.admin title="カテゴリ管理" active="categories">
    {{-- ①〜③ 追加 --}}
    <div class="mq-panel">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="d-flex align-items-end gap-2" novalidate data-submit-once>
            @csrf
            <div class="mq-field-lg">
                <label class="form-label" for="name">カテゴリ名</label>
                <input type="text" id="name" name="name" value="{{ $editingId ? '' : old('name') }}" class="form-control"
                       maxlength="20" placeholder="新しいカテゴリ名（20文字以内）" required>
            </div>
            <div class="mq-field-xs">
                <label class="form-label" for="sort_order">表示順</label>
                <input type="number" id="sort_order" name="sort_order" value="{{ $editingId ? $nextSortOrder : old('sort_order', $nextSortOrder) }}"
                       class="form-control" min="0" max="999" step="1">
            </div>
            <button type="submit" class="btn btn-primary px-4">追加する</button>
        </form>
    </div>

    {{-- ④ エラーメッセージ（追加・編集） --}}
    <x-form-errors class="mb-3" />
    <x-form-errors bag="edit" class="mb-3" />

    {{-- ⑤ カテゴリ一覧（表示順） --}}
    <div class="mq-table-wrap">
        <table class="mq-table">
            <thead>
                <tr><th>表示順</th><th>カテゴリ名</th><th>質問数</th><th>登録日</th><th>操作</th></tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    @php($editing = $editingId === $category->id)
                    <tr @class(['mq-edit-row', 'is-editing' => $editing]) data-edit-row>
                        <td>
                            <span class="mq-edit-row__view">{{ $category->sort_order }}</span>
                            <input type="number" form="edit-category-{{ $category->id }}" name="sort_order" aria-label="表示順"
                                   value="{{ $editing ? old('sort_order') : $category->sort_order }}"
                                   class="form-control form-control-sm mq-edit-row__field mq-field-xs" min="0" max="999" step="1">
                        </td>
                        <td>
                            <span class="mq-edit-row__view">{{ $category->name }}</span>
                            <input type="text" form="edit-category-{{ $category->id }}" name="name" aria-label="カテゴリ名"
                                   value="{{ $editing ? old('name') : $category->name }}"
                                   class="form-control form-control-sm mq-edit-row__field" maxlength="20">
                        </td>
                        <td>{{ $category->questions_count }}</td>
                        <td>{{ $category->created_at->format('Y/m/d') }}</td>
                        <td class="text-nowrap">
                            {{-- ⑥ 編集（行を編集モードにして更新） --}}
                            <form method="POST" action="{{ route('admin.categories.update', $category) }}" id="edit-category-{{ $category->id }}"
                                  class="d-inline" novalidate data-submit-once>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="editing" value="{{ $category->id }}">
                                <button type="button" class="btn btn-outline-primary mq-btn-xs mq-edit-row__view" data-edit-toggle>編集</button>
                                <button type="submit" class="btn btn-primary mq-btn-xs mq-edit-row__field">保存</button>
                                <button type="button" class="btn btn-outline-secondary mq-btn-xs mq-edit-row__field" data-edit-toggle>キャンセル</button>
                            </form>
                            {{-- ⑦ 削除（確認モーダル。質問が紐づく場合はサーバーで拒否） --}}
                            <button type="button" class="btn btn-danger mq-btn-xs mq-edit-row__view"
                                    data-bs-toggle="modal" data-bs-target="#delete-category-modal"
                                    data-confirm-action="{{ route('admin.categories.destroy', $category) }}"
                                    data-confirm-message="{{ $category->name }}（質問数 {{ $category->questions_count }}）">削除</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">カテゴリはまだありません</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-confirm-modal id="delete-category-modal" title="カテゴリを削除しますか？"
                     description="次のカテゴリを削除します。質問が登録されているカテゴリは削除できません。"
                     confirm-label="削除する" method="DELETE" />
</x-layouts.admin>
