<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public static function publicPages(): array
    {
        return [
            ['/', 'Надежные прокси'],
            ['/instructions', 'Инструкция по использованию прокси'],
            ['/contacts', 'Контакты'],
            ['/terms', 'Правила сервиса'],
            ['/privacy', 'Политика конфиденциальности'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_public_pages_are_available(string $uri, string $expectedText): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertSee($expectedText)
            ->assertSee('charset="UTF-8"', false)
            ->assertSee('data-menu-toggle', false);
    }

    public function test_future_sections_return_safe_placeholder_pages(): void
    {
        $this->get('/login')->assertOk()->assertSee('Вход пока недоступен');
        $this->get('/register')->assertOk()->assertSee('Регистрация пока недоступна');
        $this->get('/purchase')->assertOk()->assertSee('Покупка пока недоступна');
    }
}
