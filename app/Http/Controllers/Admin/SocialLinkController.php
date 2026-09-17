<?php

namespace App\Http\Controllers\Admin;

use App\Models\SocialLink;
use App\Support\SocialPlatforms;
use Illuminate\Validation\Rule;

class SocialLinkController extends CrudController
{
    protected string $model = SocialLink::class;

    protected string $resource = 'socials';

    protected string $singular = 'Social link';

    protected string $plural = 'Social links';

    protected function fields(): array
    {
        return [
            'platform' => [
                'type' => 'select',
                'options' => SocialPlatforms::options(),
                'rules' => ['required', Rule::in(array_keys(SocialPlatforms::ALL))],
            ],
            'url' => [
                'label' => 'Profile link or username',
                'rules' => ['required', 'string', 'max:255'],
                'help' => 'Paste the full link, or just the username (e.g. @itshamdiko). WhatsApp: your number with country code.',
            ],
            'label' => ['label' => 'Display text', 'rules' => ['nullable', 'string', 'max:80'], 'help' => 'Optional. Defaults to @username.'],
            'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:65535']],
            'is_visible' => ['label' => 'Show on the website', 'type' => 'checkbox'],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Platform', 'display' => 'Shown as', 'is_visible' => 'Visible'];
    }

    protected function prepare(array $data): array
    {
        $data['url'] = SocialPlatforms::normalize($data['platform'], $data['url']);

        if (! filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['url' => 'Enter a valid link or username.']);
        }

        return $data;
    }
}
