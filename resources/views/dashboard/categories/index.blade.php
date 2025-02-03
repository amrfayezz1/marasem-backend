@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
<!-- <link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet"> -->
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Categories</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search Bar -->
        <form method="GET" action="{{ route('dashboard.categories.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['filter']))
                    <a href="{{ route('dashboard.categories.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..." placeholder="Search..."
                    required style="flex: 3;" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}">
                <select name="filter" class="form-select me-2" required>
                    <option value="id" {{ isset($_GET['filter']) && $_GET['filter'] == 'id' ? 'selected' : '' }}>ID
                    </option>
                    <option value="name" {{ isset($_GET['filter']) && $_GET['filter'] == 'name' ? 'selected' : '' }}>
                        Name
                    </option>
                    <option value="status" {{ isset($_GET['filter']) && $_GET['filter'] == 'status' ? 'selected' : '' }}>
                        Status
                    </option>
                </select>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    @if ($categories->isEmpty())
        <center class="alert alert-warning">No categories found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td><input type="checkbox" name="category_ids[]" value="{{ $category->id }}"></td>
                        <td>{{ $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>
                            <span class="badge {{ $category->status == 'active' ? 'bg-warning' : 'bg-danger' }}">
                                {{ ucfirst($category->status) }}
                            </span>
                        </td>
                        <td>
                            <span onclick="previewCategory({{ $category->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editCategory({{ $category->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.categories.destroy', $category->id) }}" method="POST"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $categories->links() }}

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.categories.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">Delete Selected</button>
        </form>
    @endif
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.categories.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="categoryTabs">
                        @foreach($languages as $language)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                    href="#lang-{{ $language->id }}">
                                    {{ $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="lang-{{ $language->id }}">
                                <label class="mt-2">Category Name:</label>
                                <input type="text" name="translations[{{ $loop->index }}][name]" class="form-control"
                                    required>
                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Selection (Outside Tabs) -->
                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editCategoryTabs">
                        @foreach($languages as $language)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                    href="#edit-lang-{{ $language->id }}">
                                    {{ $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="edit-lang-{{ $language->id }}">
                                <label class="mt-2">Category Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][name]"
                                    class="form-control def_name">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Selection (Outside Tabs) -->
                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Category Modal -->
<div class="modal fade" id="previewCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Loading...
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var firstTabEl = document.querySelector('#categoryTabs .nav-link.active');
        var firstEditTabEl = document.querySelector('#editCategoryTabs .nav-link.active');

        if (firstTabEl) new bootstrap.Tab(firstTabEl).show();
        if (firstEditTabEl) new bootstrap.Tab(firstEditTabEl).show();
    });

    function previewCategory(categoryId) {
        $.ajax({
            url: `/dashboard/categories/${categoryId}`,
            type: 'GET',
            success: function (response) {
                let category = response;
                $('#previewCategoryModal .modal-body').html(`
                    <h5><strong>Category Name:</strong> ${category.name}</h5>
                    <p><strong>Status:</strong> ${category.status}</p>
                `);
                $('#previewCategoryModal').modal('show');
            },
            error: function () {
                alert('Failed to load category details.');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this category? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }

    function editCategory(categoryId) {
        $.ajax({
            url: `/dashboard/categories/${categoryId}`,
            type: 'GET',
            success: function (response) {
                let category = response;
                $('#editCategoryModal select[name="status"]').val(category.status);

                // Populate translations
                $('.def_name').val(category.name);
                category.translations.forEach(translation => {
                    $(`#editCategoryModal input[name="translations[${translation.language_id}][name]"]`).val(translation.name);
                });

                $('#editCategoryModal form').attr('action', `/dashboard/categories/${categoryId}`);
                $('#editCategoryModal').modal('show');
            },
            error: function () {
                alert('Failed to load category details.');
            }
        });
    }

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="category_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('bulkDeleteForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            e.preventDefault();
            alert('Select at least one category to delete.');
        } else {
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
            console.log(selectedIds);
            if (confirm('Are you sure you want to delete the selected categories?')) {
                this.submit();
            }
        }
    });
</script>

<script>
    document.querySelector('#categories').classList.add('active');
    document.querySelector('#categories .nav-link ').classList.add('active');
</script>
@endsection