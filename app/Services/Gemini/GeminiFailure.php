<?php

namespace App\Services\Gemini;

/**
 * Gemini API 呼び出しの失敗の種類（ログには種類だけを残す）
 */
enum GeminiFailure: string
{
    case MissingKey = 'missing_key';
    case RateLimited = 'rate_limited';
    case Connection = 'connection';
    case ServerError = 'server_error';
    case ClientError = 'client_error';
    case Blocked = 'blocked';
    case EmptyResponse = 'empty_response';
    case InvalidResponse = 'invalid_response';
}
