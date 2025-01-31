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
    <h3>Add How It Works Step</h3>
    <hr>
    <form action="{{ route('how.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" placeholder="Enter title" required>
        </div>

        <div class="form-group mt-3">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" placeholder="Enter description"
                required></textarea>
        </div>

        <div class="form-group mt-3">
            <label for="icon">Icon</label>
            <select name="icon" id="icon" class="form-select">
                @foreach ($icons as $icon)
                    <option value="{{ $icon }}" data-icon="{{ $icon }}">
                        {{ $icon }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
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