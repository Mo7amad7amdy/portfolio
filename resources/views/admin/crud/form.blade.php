@extends('admin.layout')

@php $editing = $item->exists; @endphp

@section('title', ($editing ? 'Edit ' : 'New ').strtolower($singular))

@section('actions')
    <a class="btn btn-light" href="{{ route("admin.$resource.index") }}">← Back</a>
@endsection

@section('content')
<form method="POST"
      action="{{ $editing ? route("admin.$resource.update", $item->getKey()) : route("admin.$resource.store") }}"
      class="card form-card">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="form-grid">
        @foreach ($fields as $name => $field)
            @php
                $value = $item->{$name};
                if ($value instanceof \DateTimeInterface) {
                    $value = $field['type'] === 'month' ? $value->format('Y-m') : $value->format('Y-m-d');
                }
                $value = old($name, $value);
                $listId = $field['suggestions'] ? "list-$name" : null;
            @endphp

            @if ($field['type'] === 'checkbox')
                <label class="check field-center {{ $field['wide'] ? 'wide' : '' }}">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value)>
                    {{ $field['label'] }}
                </label>
            @else
                <label class="field {{ $field['wide'] ? 'wide' : '' }}">
                    <span>{{ $field['label'] }} @if (in_array('required', $field['rules'], true))<b class="req">*</b>@endif</span>
                    @if ($field['type'] === 'textarea')
                        <textarea name="{{ $name }}" rows="6">{{ $value }}</textarea>
                    @elseif ($field['type'] === 'select')
                        <select name="{{ $name }}">
                            @foreach ($field['options'] as $optValue => $optLabel)
                                <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $value }}"
                               @if ($listId) list="{{ $listId }}" @endif
                               @if ($field['type'] === 'number') inputmode="numeric" @endif>
                        @if ($listId)
                            <datalist id="{{ $listId }}">
                                @foreach ($field['suggestions'] as $suggestion)<option value="{{ $suggestion }}">@endforeach
                            </datalist>
                        @endif
                    @endif
                    @if ($field['help'])<small class="hint">{{ $field['help'] }}</small>@endif
                    @error($name)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endif
        @endforeach
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Create' }}</button>
        <a class="btn btn-text" href="{{ route("admin.$resource.index") }}">Cancel</a>
    </div>
</form>
@endsection
