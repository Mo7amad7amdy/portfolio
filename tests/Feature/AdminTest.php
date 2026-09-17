<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/experiences')->assertRedirect(route('login'));
    }

    public function test_admin_can_log_in_with_seeded_credentials(): void
    {
        $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_every_dashboard_page_renders(): void
    {
        $this->actingAs($this->admin);
        $message = Message::create(['name' => 'A', 'email' => 'a@example.com', 'body' => 'Hello there, friend']);

        $pages = ['/admin', '/admin/profile', '/admin/account', '/admin/messages', "/admin/messages/{$message->id}"];
        foreach (['socials', 'stats', 'experiences', 'projects', 'skills', 'certifications', 'educations', 'languages'] as $resource) {
            $pages[] = "/admin/{$resource}";
            $pages[] = "/admin/{$resource}/create";
        }
        $pages[] = '/admin/experiences/'.Experience::first()->id.'/edit';

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }

        $this->assertNotNull($message->fresh()->read_at, 'Opening a message marks it as read.');
    }

    public function test_experience_crud(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/experiences', [
            'role' => 'Lead Engineer', 'company' => 'Acme', 'start_date' => '2026-01',
            'is_current' => '1', 'description' => "Did things\nDid more things", 'tech' => 'Laravel, Go',
        ])->assertRedirect(route('admin.experiences.index'));

        $job = Experience::where('company', 'Acme')->firstOrFail();
        $this->assertTrue($job->is_current);
        $this->assertSame('2026-01-01', $job->start_date->toDateString());
        $this->assertSame(['Did things', 'Did more things'], $job->bullets());

        $this->put("/admin/experiences/{$job->id}", [
            'role' => 'Principal Engineer', 'company' => 'Acme', 'start_date' => '2026-01',
            'is_current' => '0', 'end_date' => '2026-06',
        ])->assertRedirect();
        $this->assertSame('Principal Engineer', $job->fresh()->role);
        $this->assertSame('Jan 2026 — Jun 2026', $job->fresh()->period);

        $this->delete("/admin/experiences/{$job->id}")->assertRedirect();
        $this->assertModelMissing($job);
    }

    public function test_end_date_required_unless_current(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/experiences', ['role' => 'X', 'company' => 'Y', 'start_date' => '2020-01', 'is_current' => '0'])
            ->assertSessionHasErrors('end_date');
    }

    public function test_skill_crud_and_site_reflects_changes(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/skills', ['name' => 'Kubernetes', 'category' => 'Cloud & DevOps', 'level' => 70])
            ->assertRedirect();
        $this->assertDatabaseHas('skills', ['name' => 'Kubernetes', 'sort_order' => 0]);

        $this->get('/')->assertSee('Kubernetes');

        $skill = Skill::where('name', 'Kubernetes')->first();
        $this->delete("/admin/skills/{$skill->id}");
        $this->get('/')->assertDontSee('Kubernetes');
    }

    public function test_profile_update_with_photo_upload(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', [
            'name' => 'Mohammed Hamdy',
            'title' => 'Staff Backend Engineer',
            'open_to_work' => '0',
            'photo' => UploadedFile::fake()->image('me.jpg', 600, 750),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $profile = Profile::current();
        $this->assertSame('Staff Backend Engineer', $profile->title);
        $this->assertFalse($profile->open_to_work);
        $this->assertStringStartsWith('uploads/photos/', $profile->photo);
        $this->assertFileExists(public_path($profile->photo));

        File::delete(public_path($profile->photo));
    }

    public function test_removing_hero_layers_falls_back_to_photo_mode(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', [
            'name' => 'M', 'title' => 'T', 'remove_hero_layers' => '1', 'remove_living_portrait' => '1',
        ])->assertRedirect();

        $this->assertFalse(Profile::current()->hasHeroLayers());
        $this->assertFalse(Profile::current()->hasLivingPortrait());
        // Bundled default images are never deleted.
        $this->assertFileExists(public_path('images/hero-head.webp'));
        $this->get('/')->assertSee('data-mode="photo"', false);
    }

    public function test_living_portrait_is_the_default_hero(): void
    {
        $profile = Profile::current();

        $this->assertSame('living', $profile->heroMode());
        $this->assertFileExists(public_path($profile->hero_portrait));
        $this->assertFileExists(public_path($profile->hero_depth));
    }

    public function test_turning_off_living_portrait_falls_back_to_layers(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', ['name' => 'M', 'title' => 'T', 'remove_living_portrait' => '1'])->assertRedirect();

        $this->assertSame('layers', Profile::current()->heroMode());
        $this->assertFileExists(public_path('images/portrait/portrait.webp'));
        $this->get('/')->assertSee('data-mode="layers"', false)->assertDontSee('living-portrait.js');
    }

    public function test_new_portrait_without_closed_eyes_drops_the_old_closed_image(): void
    {
        $this->actingAs($this->admin);
        $this->assertNotNull(Profile::current()->hero_closed);

        $this->put('/admin/profile', [
            'name' => 'M', 'title' => 'T',
            'hero_portrait' => UploadedFile::fake()->image('me.png', 500, 625),
        ])->assertSessionHasNoErrors();

        $profile = Profile::current();
        $this->assertNull($profile->hero_closed);
        $this->assertStringStartsWith('uploads/portrait/', $profile->hero_portrait);
        $this->assertFileExists(public_path('images/portrait/portrait-closed.webp'), 'Bundled files are never deleted.');
        $this->get('/')->assertDontSee('data-closed=', false);

        File::delete(public_path($profile->hero_portrait));
    }

    public function test_social_link_from_username_is_normalised_and_shown(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/socials', ['platform' => 'instagram', 'url' => '@itsHamdiko', 'is_visible' => '1', 'sort_order' => 0])
            ->assertRedirect(route('admin.socials.index'))
            ->assertSessionHasNoErrors();

        $link = SocialLink::where('platform', 'instagram')->firstOrFail();
        $this->assertSame('https://instagram.com/itsHamdiko', $link->url);
        $this->assertSame('@itsHamdiko', $link->display);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('href="https://instagram.com/itsHamdiko"', $html);
        $this->assertStringContainsString('<link rel="me" href="https://instagram.com/itsHamdiko">', $html);
        $this->assertStringContainsString('"https://instagram.com/itsHamdiko"', $html); // schema.org sameAs

        // Hidden links disappear from the site.
        $this->put("/admin/socials/{$link->id}", ['platform' => 'instagram', 'url' => $link->url, 'is_visible' => '0']);
        $this->get('/')->assertDontSee('instagram.com/itsHamdiko', false);
    }

    public function test_whatsapp_number_and_invalid_platform(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/socials', ['platform' => 'whatsapp', 'url' => '+20 111 895 2279', 'is_visible' => '1']);
        $this->assertDatabaseHas('social_links', ['url' => 'https://wa.me/201118952279']);

        $this->post('/admin/socials', ['platform' => 'myspace', 'url' => 'x'])->assertSessionHasErrors('platform');
    }

    public function test_seo_fields_are_saved_and_used(): void
    {
        $this->actingAs($this->admin);
        $before = $this->shareCard();

        $this->put('/admin/profile', [
            'name' => 'Mohammed Hamdy', 'title' => 'Staff Laravel Engineer',
            'seo_title' => 'Hire a Laravel backend engineer',
            'seo_description' => 'APIs, performance and cloud for growing products.',
            'twitter_handle' => '@mo7amad',
        ])->assertSessionHasNoErrors();

        $this->get('/')
            ->assertSee('<title>Hire a Laravel backend engineer</title>', false)
            ->assertSee('content="APIs, performance and cloud for growing products."', false)
            ->assertSee('<meta name="twitter:site" content="@mo7amad">', false);

        // The title changed, so a new card was generated and the old one removed.
        $after = $this->shareCard();
        $this->assertNotSame($before, $after);
        $this->assertFileDoesNotExist(public_path($before));
        $this->assertFileExists(public_path($after));
    }

    public function test_invalid_twitter_handle_is_rejected(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', ['name' => 'M', 'title' => 'T', 'twitter_handle' => 'not a handle!'])
            ->assertSessionHasErrors('twitter_handle');
    }

    public function test_custom_share_image_replaces_the_generated_card(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', [
            'name' => 'M', 'title' => 'T',
            'seo_image' => UploadedFile::fake()->image('share.jpg', 1200, 630),
        ])->assertSessionHasNoErrors();

        $image = Profile::current()->seo_image;
        $this->get('/')->assertSee('<meta property="og:image" content="'.asset($image).'">', false);

        File::delete(public_path($image));
    }

    private function shareCard(): string
    {
        preg_match('#images/social/card-[a-f0-9]{12}\.png#', $this->get('/')->getContent(), $m);

        return $m[0];
    }

    public function test_eye_picker_meta_is_normalised(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', [
            'name' => 'M', 'title' => 'T', 'portrait_focus' => '0.4',
            // right eye first, one value out of range: the server sorts and clamps.
            'hero_meta' => json_encode(['eyes' => [
                ['x' => 0.6, 'y' => 0.3, 'rx' => 0.04, 'ry' => 0.01],
                ['x' => 0.4, 'y' => 1.7, 'rx' => 0.04, 'ry' => 0.01],
            ]]),
        ])->assertSessionHasNoErrors();

        $meta = Profile::current()->hero_meta;
        $this->assertSame(0.4, $meta['eyes'][0]['x']);
        $this->assertEquals(1, $meta['eyes'][0]['y']);
        $this->assertEquals(0.4, $meta['focus']);
        $this->assertArrayHasKey('head', $meta);
    }

    public function test_eye_picker_requires_two_eyes(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/profile', [
            'name' => 'M', 'title' => 'T',
            'hero_meta' => json_encode(['eyes' => [['x' => 0.4, 'y' => 0.3]]]),
        ])->assertSessionHasErrors('hero_meta');
    }

    public function test_account_password_change_requires_current_password(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/account', [
            'name' => 'Mo', 'email' => $this->admin->email, 'current_password' => 'nope',
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->put('/admin/account', [
            'name' => 'Mo', 'email' => $this->admin->email, 'current_password' => 'password',
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password-123', $this->admin->fresh()->password));
    }
}
