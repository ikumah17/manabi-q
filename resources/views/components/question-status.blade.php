{{-- 質問の状態バッジ（未解決／解決済み） --}}
@props(['resolved'])
@if ($resolved)
    <x-badge variant="success">解決済み</x-badge>
@else
    <x-badge variant="danger">未解決</x-badge>
@endif
