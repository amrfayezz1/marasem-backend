@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Sub-Categories</h3>
        <button data-bs-toggle="modal" data-bs-target="#addTagModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search & Filter Bar -->
        <form method="GET" action="{{ route('dashboard.tags.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['category']))
                    <a href="{{ route('dashboard.tags.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..." placeholder="Search..."
                    style="flex: 3;" value="{{ request('search', '') }}">
                <select name="category" class="form-select">
                    <option value="all">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Tags Table -->
    @if ($tags->isEmpty())
        <center class="alert alert-warning">No subcategories found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <th>Subcategory Name</th>
                    <th>Parent Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr>
                        <td><input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"></td>
                        <td>{{ $tag->name }}</td>
                        <td>{{ $tag->category->name }}</td>
                        <td>
                            <span class="badge {{ $tag->status == 'published' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($tag->status) }}
                            </span>
                        </td>
                        <td>
                            <span onclick="previewTag({{ $tag->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editTag({{ $tag->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.tags.destroy', $tag->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $tags->links() }}

        <!-- Bulk Delete / Publish / Unpublish Buttons -->
        <div class="bulks">
            <form method="POST" action="{{ route('dashboard.tags.bulk-delete') }}">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" id="bulkDeleteBtn" class="btn btn-danger mt-3">Delete Selected</button>
            </form>

            <form method="POST" action="{{ route('dashboard.tags.bulk-publish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkPublishIds">
                <button type="submit" id="bulkPublishBtn" class="btn btn-success mt-3">Publish Selected</button>
            </form>

            <form method="POST" action="{{ route('dashboard.tags.bulk-unpublish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkUnpublishIds">
                <button type="submit" id="bulkUnpublishBtn" class="btn btn-warning mt-3">Unpublish Selected</button>
            </form>
        </div>
    @endif
</div>

<!-- Add Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.tags.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Parent Category -->
                    <label>Parent Category:</label>
                    <select name="category_id" class="form-control">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-2" id="tagTabs">
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
                                <label class="mt-2">Subcategory Name:</label>
                                <input type="text" name="translations[{{ $loop->index }}][name]" class="form-control"
                                    required>
                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Selection -->
                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="published">Published</option>
                        <option value="hidden">Hidden</option>
                    </select>

                    <!-- Meta Keyword -->
                    <!-- <label class="mt-2">Meta Keyword:</label>
                    <input type="text" name="meta_keyword" class="form-control" required> -->

                    <!-- URL -->
                    <!-- <label class="mt-2">URL:</label>
                    <input type="text" name="url" class="form-control" required> -->

                    <!-- Picture -->
                    <!-- <label class="mt-2">Picture:</label>
                    <input type="file" name="image" class="form-control" required> -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Parent Category -->
                    <label>Parent Category:</label>
                    <select name="category_id" class="form-control">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-2" id="editTagTabs">
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
                                <label class="mt-2">Subcategory Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][name]"
                                    class="form-control def_name">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Status Selection -->
                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="published">Published</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Tag Modal -->
<div class="modal fade" id="previewTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Subcategory</h5>
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
    function getSelectedTagIds() {
        return Array.from(document.querySelectorAll('input[name="tag_ids[]"]:checked'))
            .map(cb => cb.value);
    }

    function updateBulkActionInput(fieldId) {
        const selectedIds = getSelectedTagIds();
        if (selectedIds.length === 0) {
            alert('Select at least one subcategory.');
            return false;
        }
        document.getElementById(fieldId).value = JSON.stringify(selectedIds);
        return true;
    }

    // Bulk Delete Function
    document.getElementById('bulkDeleteBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkDeleteIds')) {
            event.preventDefault();
        }
    });

    // Bulk Publish Function
    document.getElementById('bulkPublishBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkPublishIds')) {
            event.preventDefault();
        }
    });

    // Bulk Unpublish Function
    document.getElementById('bulkUnpublishBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkUnpublishIds')) {
            event.preventDefault();
        }
    });

    // Select All Checkbox Handler
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="tag_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

</script>

<script>
    function editTag(tagId) {
        $.ajax({
            url: `/dashboard/sub-categories/${tagId}`,
            type: 'GET',
            success: function (response) {
                let tag = response.tag;
                $('#editTagModal select[name="category_id"]').val(tag.category_id);
                $('#editTagModal select[name="status"]').val(tag.status);

                // Populate translations
                tag.translations.forEach(translation => {
                    $(`#editTagModal input[name="translations[${translation.language_id}][name]"]`).val(translation.name);
                });

                $('#editTagModal form').attr('action', `/dashboard/sub-categories/${tagId}`);
                $('#editTagModal').modal('show');
            },
            error: function () {
                alert('Failed to load subcategory details.');
            }
        });
    }
</script>

<script>
    function previewTag(tagId) {
        $.ajax({
            url: `/dashboard/sub-categories/${tagId}`,
            type: 'GET',
            success: function (response) {
                let tag = response.tag;
                console.log(tag);
                let translations = tag.translations.map(t => `<p><strong>${t.language ? t.language.name : ''}:</strong> ${t.name}</p>`).join('');

                $('#previewTagModal .modal-body').html(`
                <h5><strong>Subcategory Name:</strong> ${tag.name}</h5>
                <p><strong>Parent Category:</strong> ${tag.category.name}</p>
                <p><strong>Status:</strong> ${tag.status}</p>
                <h6>Translations:</h6>
                ${translations}
            `);
                $('#previewTagModal').modal('show');
            },
            error: function () {
                alert('Failed to load subcategory details.');
            }
        });
    }
</script>

<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this subcategory? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="tag_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

    document.querySelector('#sub-categories').classList.add('active');
    document.querySelector('#sub-categories .nav-link ').classList.add('active');
</script>
@endsection