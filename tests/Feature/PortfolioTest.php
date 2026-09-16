<?php

namespace Tests\Feature;

use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_cv_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Mohammed Hamdy')
            ->assertSee('Senior Backend Developer')
            ->assertSee('Taqeem')
            ->assertSee('VYA Auction Platform')
            ->assertSee('data-face-stage', false)
            ->assertSee('data-mode="living"', false)
            ->assertSee('portrait-depth.png', false)
            ->assertSee('js/living-portrait.js', false);
    }

    public function test_home_page_renders_without_any_data(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/contact', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'subject' => 'Project',
            'body' => 'We need a Laravel API built.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['email' => 'jane@example.com', 'read_at' => null]);
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post('/contact', ['name' => '', 'email' => 'nope', 'body' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'body']);

        $this->assertSame(0, Message::count());
    }

    public function test_honeypot_silently_drops_spam(): void
    {
        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'body' => 'Buy cheap things now!!!', 'website' => 'http://spam.test',
        ])->assertRedirect();

        $this->assertSame(0, Message::count());
    }
}
