{{-- ページネーション本体（< 1 2 3 >）。mobile / admin から読み込む --}}
@if ($paginator->hasPages())
    <nav @class(['mq-pagination', 'mq-pagination--admin' => $admin ?? false]) aria-label="ページ切り替え">
        @if ($paginator->onFirstPage())
            <span class="mq-page is-disabled" aria-disabled="true" aria-label="前のページ"><x-icon name="chevron-left" /></span>
        @else
            <a class="mq-page" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="前のページ"><x-icon name="chevron-left" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="mq-page is-disabled" aria-disabled="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="mq-page is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="mq-page" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="mq-page" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="次のページ"><x-icon name="chevron-right" /></a>
        @else
            <span class="mq-page is-disabled" aria-disabled="true" aria-label="次のページ"><x-icon name="chevron-right" /></span>
        @endif
    </nav>
@endif
