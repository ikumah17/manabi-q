{{-- 投稿日時（一般画面）：今年は「10/01 09:12」、それ以外は「2025/10/01 09:12」 --}}
@props(['at'])
<time datetime="{{ $at->toIso8601String() }}" {{ $attributes }}>{{ $at->format($at->isCurrentYear() ? 'm/d H:i' : 'Y/m/d H:i') }}</time>
