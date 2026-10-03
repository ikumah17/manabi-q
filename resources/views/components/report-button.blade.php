{{-- G7 ⑨ 通報する（自分の投稿には出さない。押すと G17 のボトムシートを開く） --}}
@props(['sheet'])
<button type="button" {{ $attributes->class('mq-report-button') }}
        data-bs-toggle="offcanvas" data-bs-target="#{{ $sheet }}"><x-icon name="flag" />通報</button>
