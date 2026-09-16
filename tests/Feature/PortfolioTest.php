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
            ->assertSee('portrait-closed.webp', false)
            ->assertSee('js/living-portrait.js', false);
    }

    public function test_home_page_has_seo_and_social_card_tags(): void
    {
        $this->seed(DatabaseSeeder::class);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<link rel="canonical" href="http[^"]+">#', $html);
        $this->assertStringContainsString('<meta property="og:type" content="profile">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="([^"]+/images/social/card-[a-f0-9]{12}\.png)">#', $html);

        preg_match('#images/social/card-[a-f0-9]{12}\.png#', $html, $m);
        $this->assertFileExists(public_path($m[0]));
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path($m[0])), 0, 2));

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $ld);
        $graph = collect(json_decode($ld[1], true)['@graph'])->keyBy('@type');
        $this->assertSame('Mohammed Hamdy', $graph['Person']['name']);
        $this->assertSame('Taqeem', $graph['Person']['worksFor']['name']);
        $this->assertContains('https://github.com/mo7amad7amdy', $graph['Person']['sameAs']);
        $this->assertArrayHasKey('ProfilePage', $graph->all());
    }

    public function test_sitemap_and_manifest(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/').'</loc>', false);

        $this->get('/site.webmanifest')
            ->assertOk()
            ->assertJsonPath('short_name', 'Mohammed Hamdy')
            ->assertJsonCount(2, 'icons');
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
