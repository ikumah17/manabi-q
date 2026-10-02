{{-- 個別の画面がない 5xx（502 など）の共通画面 --}}
@extends('errors.layout')

@section('code', isset($exception) ? (string) $exception->getStatusCode() : '')
@section('heading', 'システムエラーが発生しました')
@section('message', '申し訳ありません。時間をおいて、再度お試しください。')
