@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Sub-Categories') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addTagModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search & Filter Bar -->
        <form method="GET" action="{{ route('dashboard.tags.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['category']))
                    <a href="{{ route('dashboard.tags.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="{{ tt('Search...') }}"
                    placeholder="{{ tt('Search...') }}" style="flex: 3;" value="{{ request('search', '') }}">
                <select name="category" class="form-select">
                    <option value="all">{{ tt('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Tags Table -->
    @if ($tags->isEmpty())
        <center class="alert alert-warning">{{ tt('No subcategories found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('Subcategory Name') }}</th>
                    <th>{{ tt('Parent Category') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Actions') }}</th>
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


        @if ($tags->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$tags->onFirstPage())
                        <a href="{{ $tags->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $tags->lastPage(); $i++)
                        <a href="{{ $tags->url($i) }}">
                            <li class="page-item {{ $i == $tags->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($tags->hasMorePages())
                        <a href="{{ $tags->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Delete / Publish / Unpublish Buttons -->
        <div class="bulks">
            <form method="POST" action="{{ route('dashboard.tags.bulk-delete') }}">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" id="bulkDeleteBtn" class="btn btn-danger mt-3">{{ tt('Delete Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.tags.bulk-publish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkPublishIds">
                <button type="submit" id="bulkPublishBtn" class="btn btn-success mt-3">{{ tt('Publish Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.tags.bulk-unpublish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkUnpublishIds">
                <button type="submit" id="bulkUnpublishBtn"
                    class="btn btn-warning mt-3">{{ tt('Unpublish Selected') }}</button>
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
                    <h5 class="modal-title">{{ tt('Add Subcategory') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Parent Category -->
                    <label>{{ tt('Parent Category:') }}</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">{{ tt('Select a parent category') }}</option>
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
                                    {{ tt($language->name) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="lang-{{ $language->id }}">
                                <label class="mt-2">{{ tt('Subcategory Name:') }}</label>
                                <input type="text" name="translations[{{ $loop->index }}][name]" class="form-control"
                                    required>
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $loop->index }}][description]" class="form-control"
                                    maxlength="200" required></textarea>
                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Common Fields -->
                    <label class="mt-2">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control" required>
                        <option value="published">{{ tt('Published') }}</option>
                        <option value="hidden">{{ tt('Hidden') }}</option>
                    </select>

                    <label class="mt-2">{{ tt('Meta Keyword:') }}</label>
                    <input type="text" name="meta_keyword" class="form-control" required>

                    <label class="mt-2">{{ tt('URL:') }}</label>
                    <input type="text" name="url" class="form-control" required>

                    <label class="mt-2">{{ tt('Picture:') }}</label>
                    <input type="file" name="image" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Edit Subcategory') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Parent Category -->
                    <label>{{ tt('Parent Category:') }}</label>
                    <select name="category_id" class="form-control" required>
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
                                    {{ tt($language->name) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="edit-lang-{{ $language->id }}">
                                <label class="mt-2">{{ tt('Subcategory Name:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][name]"
                                    class="form-control def_name" required>
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $language->id }}][description]"
                                    class="form-control def_desc" maxlength="200" required></textarea>
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <!-- Common Fields -->
                    <label class="mt-2">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control" required>
                        <option value="published">{{ tt('Published') }}</option>
                        <option value="hidden">{{ tt('Hidden') }}</option>
                    </select>

                    <label class="mt-2">{{ tt('Meta Keyword:') }}</label>
                    <input type="text" name="meta_keyword" class="form-control" required>

                    <label class="mt-2">{{ tt('URL:') }}</label>
                    <input type="text" name="url" class="form-control" required>

                    <label class="mt-2">{{ tt('Picture:') }}</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
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
                <h5 class="modal-title">{{ tt('Preview Subcategory') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{ tt('Loading...') }}
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
            alert('{{ tt('Select at least one subcategory.') }}');
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
<!-- edit -->
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
                alert('{{ tt('Failed to load subcategory details.') }}');
            }
        });
    }
</script>
<!-- preview -->
<script>
    function previewTag(tagId) {
        $.ajax({
            url: `/dashboard/sub-categories/${tagId}`,
            type: 'GET',
            success: function (response) {
                let tag = response.tag;
                let translationsHtml = tag.translations.map(t => ` 
                <p><strong>${t.language ? t.language.name : ''}:</strong> ${t.name} <br>
                <em>${t.description}</em></p>
            `).join('');

                // Toggle button for visibility status
                let toggleBtn = tag.status === 'published'
                    ? `<button onclick="toggleTagStatus(${tag.id})" class="btn btn-warning">{{ tt('Unpublish') }}</button>`
                    : `<button onclick="toggleTagStatus(${tag.id})" class="btn btn-success">{{ tt('Publish') }}</button>`;

                $('#previewTagModal .modal-body').html(`
                <h5><strong>{{ tt('Subcategory Name:') }}</strong> ${tag.name}</h5>
                <p><strong>{{ tt('Description:') }}</strong> ${tag.description ? tag.description : '{{ tt('No description provided.') }}'}</p>
                <p><strong>{{ tt('Parent Category:') }}</strong> ${tag.category.name}</p>
                <p><strong>{{ tt('Status:') }}</strong> ${tag.status} ${toggleBtn}</p>
                <p><strong>{{ tt('Meta Keyword:') }}</strong> ${tag.meta_keyword}</p>
                <p><strong>{{ tt('URL:') }}</strong> ${tag.url}</p>
                <p><strong>{{ tt('Picture:') }}</strong> ${tag.image ? `<img src="${tag.image}" alt="Subcategory Picture" style="max-width: 100px;">` : '{{ tt('No picture available') }}'}</p>
                <h6>{{ tt('Translations:') }}</h6>
                ${translationsHtml}
            `);
                $('#previewTagModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load subcategory details.') }}');
            }
        });
    }

    function toggleTagStatus(tagId) {
        $.ajax({
            url: `/dashboard/sub-categories/${tagId}/toggle-status`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    alert('{{ tt('Visibility status updated successfully.') }}');
                    previewTag(tagId); // refresh preview details
                }
            },
            error: function () {
                alert('{{ tt('Failed to update visibility status. Please try again.') }}');
            }
        });
    }
</script>

<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this subcategory? This action cannot be undone.') }}")) {
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