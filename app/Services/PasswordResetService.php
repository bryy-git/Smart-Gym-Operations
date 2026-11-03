<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function issue(User $user): string
    {
        $token = Str::random(64);
        $cache = Cache::store(config('auth.password_reset_cache_store'));
        $key = $this->cacheKey($user->email);

        $cache->put($key, [
            'user_id' => $user->user_id,
            'token_hash' => hash('sha256', $token),
        ], now()->addMinutes((int) config('auth.passwords.users.expire', 60)));

        return $token;
    }

    public function reset(string $email, string $token, string $password): bool
    {
        $cache = Cache::store(config('auth.password_reset_cache_store'));
        $key = $this->cacheKey($email);

        return $cache->lock($key.':lock', 10)->block(5, function () use (
            $cache,
            $key,
            $email,
            $token,
            $password,
        ): bool {
            $record = $cache->get($key);

            if (
                ! is_array($record)
                || ! isset($record['user_id'], $record['token_hash'])
                || ! hash_equals((string) $record['token_hash'], hash('sha256', $token))
            ) {
                return false;
            }

            $cache->forget($key);

            return DB::transaction(function () use ($record, $email, $password): bool {
                $user = User::query()
                    ->whereKey($record['user_id'])
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
                    ->where('account_status', 'Active')
                    ->lockForUpdate()
                    ->first();

                if ($user === null) {
                    return false;
                }

                $user->password = $password;

                return $user->save();
            });
        });
    }

    private function cacheKey(string $email): string
    {
        return 'auth:password-reset:'.hash('sha256', mb_strtolower(trim($email)));
    }
}
