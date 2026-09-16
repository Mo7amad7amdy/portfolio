@extends('admin.layout')

@section('title', $plural)

@section('actions')
    <a class="btn btn-primary" href="{{ route("admin.$resource.create") }}">+ Add {{ strtolower($singular) }}</a>
@endsection

@section('content')
<section class="card table-card">
    @if ($items->isEmpty())
        <p class="empty">Nothing here yet. <a href="{{ route("admin.$resource.create") }}">Add the first one</a>.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    @foreach ($columns as $label)<th>{{ $label }}</th>@endforeach
                    <th class="actions-col"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($items as $item)
                    <tr>
                        @foreach ($columns as $attr => $label)
                            @php $value = $item->{$attr}; @endphp
                            <td data-label="{{ $label }}" class="{{ $loop->first ? 'strong' : '' }}">
                                @if (is_bool($value))
                                    <span class="pill {{ $value ? 'pill-on' : '' }}">{{ $value ? 'Yes' : 'No' }}</span>
                                @elseif ($loop->first)
                                    <a href="{{ route("admin.$resource.edit", $item->getKey()) }}">{{ \Illuminate\Support\Str::limit((string) $value, 60) }}</a>
                                @else
                                    {{ \Illuminate\Support\Str::limit((string) $value, 60) ?: '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="actions-col">
                            <a class="btn btn-light btn-sm" href="{{ route("admin.$resource.edit", $item->getKey()) }}">Edit</a>
                            <form method="POST" action="{{ route("admin.$resource.destroy", $item->getKey()) }}" data-confirm="Delete this {{ strtolower($singular) }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="pager">
                @if ($items->previousPageUrl())<a class="btn btn-light btn-sm" href="{{ $items->previousPageUrl() }}">← Previous</a>@endif
                <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
                @if ($items->nextPageUrl())<a class="btn btn-light btn-sm" href="{{ $items->nextPageUrl() }}">Next →</a>@endif
            </div>
        @endif
    @endif
</section>
@endsection
