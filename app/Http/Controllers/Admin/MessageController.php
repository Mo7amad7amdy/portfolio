<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function index(): View
    {
        return view('admin.messages.index', ['messages' => Message::latest()->paginate(20)]);
    }

    public function show(Message $message): View
    {
        $message->markAsRead();

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Message deleted.');
    }
}
