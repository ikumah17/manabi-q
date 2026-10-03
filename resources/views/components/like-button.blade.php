{{--
    G7 ⑩ いいね（質問・各回答）
    - count  : いいねの件数
    - liked  : ログインユーザーがいいね済みか
    - action : 送信先（null なら自分の投稿として件数だけを表示する）
    押した状態は色だけで示さず、ハートの塗りと「いいね済み」の文言・aria-pressed で示す。
    いいね済みなら DELETE（取り消し）、未いいねなら POST（付ける）を送る
--}}
@props(['count', 'liked' => false, 'action' => null])
@if ($action)
    <form method="POST" action="{{ $action }}" class="m-0" data-submit-once>
        @csrf
        @if ($liked)
            @method('DELETE')
        @endif
        <button type="submit" class="btn btn-outline-primary btn-sm mq-btn-pill d-inline-flex align-items-center gap-1 px-3"
                aria-pressed="{{ $liked ? 'true' : 'false' }}" data-like-button>
            <x-icon :name="$liked ? 'heart-fill' : 'heart'" />{{ $liked ? 'いいね済み' : 'いいね' }} {{ $count }}
        </button>
    </form>
@else
    <span class="mq-meta d-inline-flex align-items-center gap-1" data-like-count>
        <x-icon name="heart" />いいね {{ $count }}
    </span>
@endif
