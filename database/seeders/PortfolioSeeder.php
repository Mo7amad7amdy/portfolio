<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SocialLink;
use App\Models\Stat;
use Illuminate\Database\Seeder;

/**
 * Seeds the portfolio with the CV content. Safe to re-run: it does nothing
 * once a profile exists, so dashboard edits are never overwritten.
 */
class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        if (Profile::query()->exists()) {
            return;
        }

        Profile::create([
            'name' => 'Mohammed Hamdy',
            'title' => 'Senior Backend Developer',
            'tagline' => 'I build fast, secure Laravel backends and APIs that hold up in production.',
            'summary' => "Senior Backend Developer with 7+ years of experience building scalable backend systems, optimizing RESTful APIs, and delivering secure, high-performance applications.\n\n"
                ."Proven track record of cutting query response times by 30% and deploying cloud-based solutions on AWS and DigitalOcean. I work mainly with PHP, Laravel and MySQL, following OOP, MVC, CI/CD and documented APIs (Swagger, Postman).\n\n"
                .'Comfortable in Agile/Scrum teams, mentoring junior developers, and delivering reliable software under pressure.',
            'email' => 'Mohammad-Hamdy@hotmail.com',
            'phone' => '+201118952279',
            'location' => 'Cairo, Egypt',
            'linkedin_url' => 'https://linkedin.com/in/mo7amad7amdy',
            'github_url' => 'https://github.com/mo7amad7amdy',
            'photo' => 'images/profile.jpg',
            'hero_head' => 'images/hero-head.webp',
            'hero_body' => 'images/hero-body.webp',
            'hero_portrait' => 'images/portrait/portrait.webp',
            'hero_depth' => 'images/portrait/portrait-depth.png',
            'hero_closed' => 'images/portrait/portrait-closed.webp',
            'hero_meta' => Profile::DEFAULT_PORTRAIT_META,
            'cv_file' => 'files/Mohammed-Hamdy-CV.pdf',
            'open_to_work' => true,
        ]);

        foreach ([
            ['linkedin', 'https://linkedin.com/in/mo7amad7amdy'],
            ['github', 'https://github.com/mo7amad7amdy'],
        ] as $i => [$platform, $url]) {
            SocialLink::create(['platform' => $platform, 'url' => $url, 'sort_order' => $i]);
        }

        foreach ([
            ['7+', 'Years building backends'],
            ['35%', 'Faster API responses (OZZ)'],
            ['30%', 'Lower query response times'],
            ['20%', 'Cloud hosting cost cut'],
        ] as $i => [$value, $label]) {
            Stat::create(['value' => $value, 'label' => $label, 'sort_order' => $i]);
        }

        $experiences = [
            [
                'company' => 'Taqeem', 'company_type' => 'Gov. Company', 'role' => 'Senior Backend Developer',
                'location' => 'Riyadh, KSA (Remote)', 'start_date' => '2023-11-01', 'end_date' => null, 'is_current' => true,
                'description' => "Developed and launched 6+ new features for Taqeem applications using Laravel, improving user engagement by 15%.\n"
                    ."Optimized database queries, reducing response times and improving system scalability.\n"
                    .'Collaborated directly with clients to troubleshoot and resolve backend performance issues.',
                'tech' => 'PHP, Laravel, MySQL, REST APIs, Bitbucket, JWT, Ajax',
            ],
            [
                'company' => 'CAT', 'company_type' => 'Advertising Agency', 'role' => 'Senior Backend Developer',
                'location' => 'Cairo, Egypt', 'start_date' => '2021-01-01', 'end_date' => '2023-10-01', 'is_current' => false,
                'description' => "Built the VYA Auction Platform API with Laravel 9, Redis, and Socket.IO for real-time chat.\n"
                    ."Created the Catalyst Management Platform for web and mobile using Laravel 8.\n"
                    .'Developed the NovoCare Dashboard with Twilio Video integration, increasing customer satisfaction by 25%.',
                'tech' => 'PHP, Laravel, MySQL, Git, JWT, REST APIs, Redis, Socket.IO',
            ],
            [
                'company' => 'AWStreams', 'company_type' => 'Digital Agency', 'role' => 'Senior PHP Developer',
                'location' => 'Cairo, Egypt', 'start_date' => '2018-11-01', 'end_date' => '2021-01-01', 'is_current' => false,
                'description' => "Architected RESTful APIs with Laravel for the OZZ mobile app, cutting average API response time by 35%.\n"
                    ."Worked on Dxpo.ea for companies and Smrt.Cards for individuals using Laravel 6.\n"
                    .'Deployed 2+ scalable applications on AWS, reducing hosting costs by 20%, with Firebase push notifications.',
                'tech' => 'PHP, Laravel, MySQL, Git, JWT, REST APIs, AWS, Firebase',
            ],
            [
                'company' => 'ITQAN', 'company_type' => 'Software House', 'role' => 'Junior PHP Developer',
                'location' => 'Cairo, Egypt', 'start_date' => '2017-11-01', 'end_date' => '2018-11-01', 'is_current' => false,
                'description' => 'Engineered 3 web applications with Laravel 5, Google Books API, Vue.js, Axios and Ajax, streamlining book data access and reducing API latency by 20%.',
                'tech' => 'PHP, Laravel, MySQL, Vue.js, Bootstrap, jQuery, Ajax',
            ],
            [
                'company' => 'Canyon Travel', 'company_type' => null, 'role' => 'Junior PHP Developer',
                'location' => 'Cairo, Egypt', 'start_date' => '2016-11-01', 'end_date' => '2017-02-01', 'is_current' => false,
                'description' => 'Engineered a web application for booking tourist trips, Hajj and Umrah using Laravel 5.',
                'tech' => 'PHP, Laravel, MySQL',
            ],
            [
                'company' => 'HotSoft EG', 'company_type' => null, 'role' => 'Intern',
                'location' => '6th of October City, Egypt', 'start_date' => '2015-04-01', 'end_date' => '2015-09-01', 'is_current' => false,
                'description' => "Supported clients facing issues with the ERP system and installed it for new customers.\n"
                    .'Built a new Bootstrap interface for the ERP.',
                'tech' => 'Bootstrap, ERP',
            ],
        ];
        foreach ($experiences as $i => $row) {
            Experience::create($row + ['sort_order' => $i]);
        }

        $skills = [
            'Languages' => ['PHP' => 95, 'JavaScript' => 75, 'Go' => 50],
            'Frameworks' => ['Laravel' => 95, 'CodeIgniter' => 70, 'Vue.js' => 65],
            'Databases' => ['MySQL' => 90, 'Redis' => 80],
            'APIs & Real-time' => ['REST APIs' => 95, 'GraphQL' => 70, 'Socket.IO' => 75],
            'Cloud & DevOps' => ['AWS' => 75, 'DigitalOcean' => 80, 'Git / GitHub / Bitbucket' => 90, 'CI/CD (GitHub Actions)' => 75],
            'Practices' => ['OOP' => 95, 'MVC' => 95, 'Agile / Scrum' => 85, 'OAuth2 / JWT' => 90],
            'Tooling' => ['Postman' => 90, 'Swagger' => 85, 'Bootstrap' => 80, 'jQuery / Axios' => 80, 'SEO' => 60],
        ];
        $order = 0;
        foreach ($skills as $category => $items) {
            foreach ($items as $name => $level) {
                Skill::create(['category' => $category, 'name' => $name, 'level' => $level, 'sort_order' => $order++]);
            }
        }

        $projects = [
            ['VYA Auction Platform', 'CAT', 'Real-time auction platform API with live bidding and in-app chat powered by Redis and Socket.IO.', 'Laravel 9, Redis, Socket.IO, MySQL', true],
            ['NovoCare Dashboard', 'CAT', 'Healthcare dashboard with Twilio Video consultations — lifted customer satisfaction by 25%.', 'Laravel, Twilio Video, MySQL', true],
            ['OZZ Mobile API', 'AWStreams', 'Secure REST API for the OZZ mobile app; average response time cut by 35%.', 'Laravel, JWT, MySQL, AWS', true],
            ['Catalyst Management Platform', 'CAT', 'Management platform serving both web and mobile clients from one Laravel backend.', 'Laravel 8, REST APIs, MySQL', false],
            ['Taqeem Platform Features', 'Taqeem', 'Six-plus production features for a government platform, improving engagement by 15%.', 'Laravel, MySQL, JWT', false],
            ['Dxpo.ea & Smrt.Cards', 'AWStreams', 'Business expo platform for companies and digital business cards for individuals.', 'Laravel 6, MySQL', false],
            ['Books Web Apps', 'ITQAN', 'Three book-discovery apps on top of the Google Books API with a Vue.js front end.', 'Laravel 5, Vue.js, Google Books API', false],
            ['Hajj & Umrah Booking', 'Canyon Travel', 'Booking system for tourist trips, Hajj and Umrah packages.', 'Laravel 5, MySQL', false],
        ];
        foreach ($projects as $i => [$title, $client, $summary, $tech, $featured]) {
            Project::create(compact('title', 'client', 'summary', 'tech') + ['is_featured' => $featured, 'sort_order' => $i]);
        }

        Certification::create(['title' => 'Web Development Diploma', 'issuer' => 'AMIT', 'year' => '2016', 'sort_order' => 0]);
        Certification::create(['title' => 'CCNA, MCSE, MTA 98-364', 'issuer' => 'Microsoft', 'year' => '2014', 'sort_order' => 1]);

        Education::create([
            'degree' => 'B.A. in Information Systems', 'institution' => 'Future Academy',
            'location' => 'Cairo, Egypt', 'start_year' => '2011', 'end_year' => '2015',
        ]);

        Language::create(['name' => 'Arabic', 'level' => 'Native', 'sort_order' => 0]);
        Language::create(['name' => 'English', 'level' => 'Very good', 'sort_order' => 1]);
    }
}
