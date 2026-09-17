<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Models\VisitEvent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const IPHONE_INSTAGRAM = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 345.0.0.34.108';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_visit_is_recorded_with_details(): void
    {
        $html = $this->withHeaders([
            'User-Agent' => self::IPHONE_INSTAGRAM,
            'Accept-Language' => 'ar-EG,ar;q=0.9,en;q=0.8',
            'Referer' => 'https://l.instagram.com/',
            'CF-Ray' => 'test', 'CF-IPCountry' => 'EG',
            'CF-IPCity' => 'Alexandria',
        ])->get('/?utm_campaign=launch')->assertOk()->getContent();

        $visit = Visit::sole();
        $this->assertSame('EG', $visit->country_code);
        $this->assertSame('Alexandria', $visit->city);
        $this->assertSame('ar', $visit->language);
        $this->assertSame('ar-EG', $visit->locale);
        $this->assertSame('Instagram', $visit->source);
        $this->assertSame('launch', $visit->utm_campaign);
        $this->assertSame('mobile', $visit->device);
        $this->assertSame('iOS', $visit->os);
        $this->assertFalse($visit->is_bot);
        $this->assertTrue($visit->is_new);
        $this->assertStringContainsString('data-visit="'.$visit->uuid.'"', $html);
    }

    public function test_bots_are_flagged_and_logged_in_admin_is_not_tracked(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/');
        $this->assertTrue(Visit::sole()->is_bot);

        $this->actingAs(User::first())->get('/');
        $this->assertSame(1, Visit::count());
    }

    public function test_notrack_cookie_excludes_a_device(): void
    {
        $this->get('/?notrack=1')->assertCookie('_notrack');
        $this->withCookie('_notrack', '1')->get('/');
        $this->assertSame(0, Visit::count());
    }

    public function test_beacon_updates_engagement_and_records_events(): void
    {
        $this->get('/');
        $visit = Visit::sole();

        $payload = ['v' => $visit->uuid, 'd' => 42, 's' => 80, 'w' => 390, 'h' => 844, 'vw' => 390, 'tz' => 'Africa/Cairo', 'l' => 'en-US',
            'e' => [['n' => 'cv_download'], ['n' => 'section', 't' => 'work'], ['n' => 'section', 't' => 'work'], ['n' => 'hack', 't' => 'x']]];

        $this->call('POST', '/a/collect', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload))->assertNoContent();
        // Same section again later: not duplicated.
        $this->call('POST', '/a/collect', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['v' => $visit->uuid, 'd' => 10, 'e' => [['n' => 'section', 't' => 'work']]]));

        $visit->refresh();
        $this->assertTrue($visit->is_human);
        $this->assertSame(42, $visit->duration); // never goes down
        $this->assertSame(80, $visit->scroll_depth);
        $this->assertSame('390x844', $visit->screen);
        $this->assertSame('Africa/Cairo', $visit->timezone);
        $this->assertSame(1, VisitEvent::where('name', 'cv_download')->count());
        $this->assertSame(1, VisitEvent::where('name', 'section')->count());
        $this->assertSame(0, VisitEvent::where('name', 'hack')->count());
    }

    public function test_beacon_ignores_unknown_visits(): void
    {
        $this->call('POST', '/a/collect', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['v' => 'not-a-uuid']))->assertNoContent();
        $this->call('POST', '/a/collect', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{broken')->assertNoContent();
        $this->assertSame(0, VisitEvent::count());
    }

    public function test_dashboard_shows_breakdowns_filters_and_export(): void
    {
        $this->withHeaders(['CF-Ray' => 't', 'CF-IPCountry' => 'EG', 'CF-IPCity' => 'Cairo', 'Accept-Language' => 'en-US'])->get('/');
        $this->withHeaders(['CF-Ray' => 't', 'CF-IPCountry' => 'SA', 'CF-IPCity' => 'Riyadh', 'Accept-Language' => 'ar-SA'])->get('/');
        $this->actingAs(User::first());

        foreach (['today', '7d', '30d', '90d', '12m', 'all'] as $range) {
            $this->get('/admin/analytics?range='.$range)->assertOk()->assertSee('🇪🇬 Cairo')->assertSee('🇸🇦 Riyadh');
        }

        $this->get('/admin/analytics?country_code=SA')->assertOk()->assertSee('🇸🇦 Riyadh')->assertDontSee('🇪🇬 Cairo');

        $csv = $this->get('/admin/analytics/export?range=all')->assertOk()->streamedContent();
        $this->assertStringContainsString('Riyadh', $csv);
        $this->assertSame(3, substr_count(trim($csv), "\n") + 1);

        $this->get('/admin')->assertOk()->assertSee('Visitors today');
    }

    public function test_access_log_import(): void
    {
        $log = tempnam(sys_get_temp_dir(), 'log');
        file_put_contents($log, implode("\n", [
            '41.32.5.9 - - [01/Sep/2026:10:00:00 +0000] "GET /?utm_source=linkedin HTTP/2.0" 200 1000 "https://www.linkedin.com/" "Mozilla/5.0 (Windows NT 10.0) Chrome/128.0 Safari/537.36"',
            '41.32.5.9 - - [01/Sep/2026:10:00:01 +0000] "GET /css/site.css HTTP/2.0" 200 1000 "-" "Mozilla/5.0"',
            '66.249.66.1 - - [02/Sep/2026:10:00:00 +0000] "GET / HTTP/1.1" 200 1000 "-" "Googlebot/2.1"',
            'garbage line',
        ]));

        $this->artisan('analytics:import-log', ['files' => [$log]])->assertSuccessful();
        $this->artisan('analytics:import-log', ['files' => [$log]])->assertSuccessful(); // idempotent

        $this->assertSame(2, Visit::where('imported', true)->count());
        $row = Visit::where('is_bot', false)->sole();
        $this->assertSame('LinkedIn', $row->source);
        $this->assertSame('41.32.5.0', $row->ip);
        $this->assertSame('2026-09-01 10:00:00', $row->created_at->utc()->toDateTimeString());
        unlink($log);
    }
}
