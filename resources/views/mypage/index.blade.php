{{-- G11 マイページ --}}
<x-layouts.mobile header="マイページ" tab="mypage">
    {{-- ① ユーザー情報 --}}
    <section class="mq-card mb-3" aria-label="ユーザー情報">
        <div class="mq-profile">
            <span class="mq-profile__avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
            <div class="mq-profile__body">
                <div class="mq-profile__name">{{ $user->name }}</div>
                <div class="mq-profile__email">{{ $user->email }}</div>
                <div class="mq-profile__stats">
                    <span>質問 {{ $questionCount }}</span>
                    <span>回答 {{ $answerCount }}</span>
                    <span>BA {{ $bestAnswerCount }}</span>
                    <span>{{ $rank !== null ? $rank.'位' : '順位 −' }}</span>
                </div>
            </div>
        </div>
        {{-- ⑤ プロフィール編集 --}}
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary btn-sm w-100 mt-3 mq-btn-pill">プロフィール編集</a>
    </section>

    {{-- ② 獲得スタンプ --}}
    <section class="mq-card mb-3" aria-labelledby="stamps-title">
        <h2 class="mq-card-title" id="stamps-title">獲得スタンプ</h2>
        <div class="mq-stamp-tiles">
            @foreach ($stamps as $item)
                <div @class(['mq-stamp-tile', 'is-earned' => $item['earned']])>
                    <span class="mq-stamp-tile__icon" aria-hidden="true">{{ $item['earned'] ? '★' : '🔒' }}</span>
                    <span class="mq-stamp-tile__name">{{ $item['stamp']->name }}</span>
                    <span class="mq-stamp-tile__sub">{{ $item['earned'] ? '獲得済み' : 'あと'.$item['remaining'].'回答' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ③ タブ（自分の質問／自分の回答） --}}
    <nav class="mq-tabs mb-3" aria-label="投稿の種類">
        <a href="{{ route('mypage') }}" @class(['mq-tabs__item', 'is-active' => $tab === 'questions'])
           @if ($tab === 'questions') aria-current="page" @endif>自分の質問（{{ $questionCount }}）</a>
        <a href="{{ route('mypage', ['tab' => 'answers']) }}" @class(['mq-tabs__item', 'is-active' => $tab === 'answers'])
           @if ($tab === 'answers') aria-current="page" @endif>自分の回答（{{ $answerCount }}）</a>
    </nav>

    {{-- ④ 投稿一覧（クリックで G7） --}}
    <div class="mq-stack">
        @forelse ($list as $item)
            @if ($tab === 'answers')
                <x-question-card :question="$item->question" :href="route('questions.show', $item->question).'#answer-'.$item->id"
                                 :at="$item->created_at" :compact="true" />
            @else
                <x-question-card :question="$item" :compact="true" />
            @endif
        @empty
            <p class="mq-empty">{{ $tab === 'answers' ? 'まだ回答はありません' : 'まだ質問はありません' }}</p>
        @endforelse
    </div>

    {{ $list->links() }}

    {{-- ⑥ ログアウト --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-outline-danger bg-white w-100 mq-logout">ログアウト</button>
    </form>
</x-layouts.mobile>
