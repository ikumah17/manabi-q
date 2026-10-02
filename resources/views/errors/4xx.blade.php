{{-- 個別の画面がない 4xx（405 など）の共通画面 --}}
@extends('errors.layout')

@section('code', isset($exception) ? (string) $exception->getStatusCode() : '')
@section('heading', 'リクエストを処理できませんでした')
@section('message', 'URL が正しいか確認し、トップから操作し直してください。')
