@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Languages</h3>
        <button data-bs-toggle="modal" data-bs-target="#addLanguageModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.languages.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']))
                    <a href="{{ route('dashboard.languages.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..."
                    placeholder="Search by Code or Name..." value="{{ request('search', '') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Languages Table -->
    @if ($languages->isEmpty())
        <center class="alert alert-warning">No languages found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($languages as $language)
                    <tr>
                        <td>{{ $language->code }}</td>
                        <td>{{ $language->name }}</td>
                        <td>
                            <form method="POST" action="{{ route('dashboard.languages.update', $language->id) }}"
                                class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="{{ $language->status ? 0 : 1 }}">
                                <button type="submit"
                                    class="btn btn-sm {{ $language->status ? 'btn-success' : 'btn-secondary' }}">
                                    {{ $language->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <span onclick="editLanguage({{ $language->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.languages.destroy', $language->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                            <span type="submit">
                                <a href="{{ route('dashboard.languages.show', $language->id) }}">
                                    <i class="fa-solid fa-language"></i>
                                </a>
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $languages->links() }}
    @endif
</div>

<!-- Add Language Modal -->
<div class="modal fade" id="addLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.languages.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Language</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Language Code:</label>
                    <input type="text" name="code" class="form-control" required maxlength="5">

                    <label class="mt-2">Name:</label>
                    <input type="text" name="name" class="form-control" required maxlength="50">

                    <label class="mt-2">Status:</label>
                    <select name="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Language Modal -->
<div class="modal fade" id="editLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Language</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Language Code:</label>
                    <input type="text" name="code" class="form-control" required maxlength="5">

                    <label class="mt-2">Name:</label>
                    <input type="text" name="name" class="form-control" required maxlength="50">

                    <label class="mt-2">Status:</label>
                    <select name="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function editLanguage(languageId) {
        $.ajax({
            url: `/dashboard/languages/${languageId}/edit`,
            type: 'GET',
            success: function (response) {
                let language = response.language;
                $('#editLanguageModal input[name="code"]').val(language.code);
                $('#editLanguageModal input[name="name"]').val(language.name);
                $('#editLanguageModal select[name="status"]').val(language.status);
                $('#editLanguageModal form').attr('action', `/dashboard/languages/${language.id}`);
                $('#editLanguageModal').modal('show');
            },
            error: function () {
                alert('Failed to load language details.');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this language? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }
</script>
<script>
    document.querySelector('#languages').classList.add('active');
    document.querySelector('#languages .nav-link ').classList.add('active');
</script>
@endsection