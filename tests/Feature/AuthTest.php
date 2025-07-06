<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;


class AuthTest extends TestCase
{
    use RefreshDatabase;

     /**
     * Выполняет «pre‑flight»‑запрос к /api/login, извлекает XSRF‑TOKEN
     * и возвращает готовые к использованию заголовки и cookie.
     */
    private function fetchCsrfArtifacts(): array
    {
        // Любой GET к /api/login (или /sanctum/csrf-cookie) выдаёт XSRF‑TOKEN‑cookie
        $response = $this->get('/api/login');

        /** @var Cookie|null $cookie */
        $cookie = collect($response->headers->getCookies())
            ->first(fn (Cookie $c) => $c->getName() === 'XSRF-TOKEN');

        // В cookie лежит исходное (не зашифрованное) значение токена
        $token = $cookie?->getValue() ?? csrf_token();

        return [
            'token'   => $token,
            'headers' => [
                'X-XSRF-TOKEN' => $token,
                'X-CSRF-TOKEN' => $token,
            ],
        ];
    }

    public function test_user_can_login_with_valid_credentials()
    {

        // Создаём пользователя
        $user = User::factory()->create();

        // Аутентифицируем пользователя
        $response = $this->actingAs($user)->get('/app');

        // Проверяем ответ
        $response->assertStatus(200);


    }


} 