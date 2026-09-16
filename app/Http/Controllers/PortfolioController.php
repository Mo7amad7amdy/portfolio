<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Stat;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class PortfolioController extends Controller
{
    public function __invoke(): View
    {
        $profile = Profile::current();

        return view('portfolio', [
            'profile' => $profile,
            'seo' => Seo::for($profile),
            'stats' => Stat::ordered()->get(),
            'experiences' => Experience::ordered()->get(),
            'skillGroups' => Skill::ordered()->get()->groupBy('category'),
            'projects' => Project::ordered()->get(),
            'certifications' => Certification::ordered()->get(),
            'educations' => Education::ordered()->get(),
            'languages' => Language::ordered()->get(),
        ]);
    }
}
