<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Visit;
use App\Models\VisitEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real visitors never see this field.
        if (filled($request->input('website'))) {
            return redirect()->to(route('home').'#contact')->with('contact_status', 'Thanks! Your message has been sent.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Message::create($data + ['ip' => $request->ip()]);

        if (is_string($request->input('_visit')) && ($visit = Visit::where('uuid', $request->input('_visit'))->first())) {
            VisitEvent::create(['visit_id' => $visit->id, 'name' => 'contact_submit', 'created_at' => now()]);
        }

        return redirect()->to(route('home').'#contact')->with('contact_status', 'Thanks! Your message has been sent.');
    }
}
