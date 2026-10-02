{{-- 獲得スタンプの表示（G10：「★ 10・50・100」。獲得なしは何も出さない） --}}
@props(['stamps'])
@if ($stamps->isNotEmpty())
    <span {{ $attributes }}><span aria-hidden="true">★</span> {{ $stamps->pluck('required_count')->implode('・') }}</span>
@endif
