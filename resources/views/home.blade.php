{{-- G4 ホーム（Phase 3 時点の仮画面。Phase 4 で質問一覧に置き換える） --}}
<x-layouts.mobile tab="home" title="ホーム">
    <p class="text-body-secondary">{{ auth()->user()->name }} さんでログインしています。（質問一覧は準備中です）</p>

    {{-- 一般ユーザーのログアウトは設計上 G11（マイページ）にある。G11 の実装までの確認用 --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-danger w-100">ログアウト</button>
    </form>
</x-layouts.mobile>
