@extends('dashboard.layouts.app')
<style>
    .select2-container--default .select2-selection--single {
        height: 38px; /* Matches Bootstrap input height */
        display: flex;
        align-items: center;
    }

    .select2-results__option {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .select2-container .select2-selection--single {
        height: 50px !important;
        display: flex !important;
        align-items: center;
    }

    .container .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 12px;
    }
</style>


@section('content')
<div class="container">
    <h3>Edit Step</h3>
    <hr>
    <form action="{{ route('how.update', $how->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group mt-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $how->title) }}"
                required>
        </div>

        <div class="form-group mt-3">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="4"
                required>{{ old('description', $how->description) }}</textarea>
        </div>

        <div class="form-group mt-3">
            <label for="icon">Icon</label>
            <select name="icon" id="icon" class="form-select">
                @foreach ($icons as $icon)
                    <option value="{{ $icon }}" data-icon="{{ $icon }}" {{ $how->icon === $icon ? 'selected' : '' }}>
                        {{ $icon }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-4">Update</button>
        <a href="{{ route('how.index') }}" class="btn btn-secondary mt-4">Cancel</a>
    </form>
</div>

<script>
    $(document).ready(function () {
        $('#icon').select2({
            width: '100%',
            templateResult: formatIcon, // For dropdown options
            templateSelection: formatIcon, // For selected value
            escapeMarkup: function (markup) {
                return markup; // Allow HTML rendering
            }
        });

        function formatIcon(icon) {
            if (!icon.id) {
                return icon.text; // Render default text for placeholder
            }
            return `<i class="${icon.element.dataset.icon}"></i> ${icon.text}`;
        }
    });
</script>
@endsection