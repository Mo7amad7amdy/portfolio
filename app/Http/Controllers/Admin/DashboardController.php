<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Visit;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'profile' => Profile::current(),
            'counts' => [
                ['Experiences', Experience::count(), 'admin.experiences.index'],
                ['Projects', Project::count(), 'admin.projects.index'],
                ['Skills', Skill::count(), 'admin.skills.index'],
                ['Certifications', Certification::count(), 'admin.certifications.index'],
            ],
            'unread' => Message::unread()->count(),
            'traffic' => [
                'today' => Visit::humans()->where('created_at', '>=', now()->startOfDay())->distinct()->count('visitor_id'),
                'week' => Visit::humans()->where('created_at', '>=', now()->subDays(7))->distinct()->count('visitor_id'),
                'topCountry' => Visit::humans()->where('created_at', '>=', now()->subDays(30))->whereNotNull('country')
                    ->selectRaw('country, count(*) as c')->groupBy('country')->orderByDesc('c')->value('country'),
            ],
            'latestMessages' => Message::latest()->limit(5)->get(),
        ]);
    }
}
