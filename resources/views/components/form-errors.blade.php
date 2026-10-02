{{--
    入力エラーの表示（各画面の「エラーメッセージ」欄。エラーがあるときだけ表示）
    bag: 1画面に複数のフォームがある場合のエラーバッグ名（例：G7 の通報シート）
--}}
@props(['bag' => 'default'])
@php
    $messages = array_values(array_unique($errors->getBag($bag)->all()));
@endphp
@if ($messages)
    <div {{ $attributes->class('mq-error') }} role="alert">
        @foreach ($messages as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif
