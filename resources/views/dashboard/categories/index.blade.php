@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
<!-- <link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet"> -->
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Categories') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search Bar -->
        <form method="GET" action="{{ route('dashboard.categories.index') }}" class="mb-3 d-flex align-items-center">
            <div class="input-group" style="flex: 1;">
                <input type="text" name="search" class="form-control" placeholder="{{ tt('Search...') }}" required
                    value="{{ request('search', '') }}">
                <select name="filter" class="form-select">
                    <option value="id" {{ request('filter') == 'id' ? 'selected' : '' }}>{{ tt('ID') }}</option>
                    <option value="name" {{ request('filter') == 'name' ? 'selected' : '' }}>{{ tt('Name') }}</option>
                    <option value="status" {{ request('filter') == 'status' ? 'selected' : '' }}>{{ tt('Status') }}
                    </option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary ms-2">{{ tt('Apply Filter') }}</button>
            <a href="{{ route('dashboard.categories.index') }}"
                class="btn btn-secondary ms-2">{{ tt('Clear Filter') }}</a>
            <div class="ms-2">
                <select name="rows" class="form-select" onchange="this.form.submit()">
                    <option value="10" {{ request('rows', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('rows') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('rows') == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Categories Table -->
    @if ($categories->isEmpty())
        <center class="alert alert-warning">{{ tt('No categories found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('ID') }}</th>
                    <th>{{ tt('Category Name') }}</th>
                    <th>{{ tt('Subcategory') }}</th>
                    <th>{{ tt('Artworks Count') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td><input type="checkbox" name="category_ids[]" value="{{ $category->id }}"></td>
                        <td>{{ $category->id }}</td>
                        <td>{{ $category->name }}</td>
                        <td>
                            @if($category->tags && !empty($category->tags))
                                @foreach($category->tags as $tag)
                                    <span class="badge bg-info">{{ $tag->name }}</span>
                                @endforeach
                            @else
                                <em>{{ tt('N/A') }}</em>
                            @endif
                        </td>
                        <td>{{ $category->artworks_count }}</td>
                        <td>
                            <span class="badge {{ $category->status == 'active' ? 'bg-warning' : 'bg-danger' }}">
                                {{ ucfirst(tt($category->status)) }}
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

        @if ($categories->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if (!$categories->onFirstPage())
                        <a href="{{ $categories->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif

                    @php
                        $total = $categories->lastPage();
                        $current = $categories->currentPage();
                        // Calculate start and end page numbers to display
                        $start = max($current - 2, 1);
                        $end = min($start + 4, $total);
                        // Adjust start if we are near the end to ensure we show 5 pages if possible
                        $start = max($end - 4, 1);
                    @endphp

                    @for ($i = $start; $i <= $end; $i++)
                        <a href="{{ $categories->url($i) }}">
                            <li class="page-item {{ $i == $current ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor

                    {{-- Next Page Link --}}
                    @if ($categories->hasMorePages())
                        <a href="{{ $categories->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.categories.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">{{ tt('Delete Selected') }}</button>
        </form>
    @endif
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.categories.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Add Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="categoryTabs">
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
                                <label class="mt-2">{{ tt('Category Name:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][name]" class="form-control"
                                    required>
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $language->id }}][description]" class="form-control"
                                    maxlength="200"></textarea>
                            </div>
                        @endforeach
                    </div>

                    <!-- Artworks Selection -->
                    <label class="mt-2">{{ tt('Artworks:') }}</label>
                    <select name="artworks[]" class="form-control select2-artwork" multiple>
                        @foreach($artworks as $artwork)
                            <option value="{{ $artwork->id }}"
                                data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                data-artist="{{ $artwork->artist->first_name }} {{ $artwork->artist->last_name }}">
                                {{ $artwork->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Common Fields -->
                    <div class="mt-3">
                        <label>{{ tt('Meta Keyword:') }}</label>
                        <input type="text" name="meta_keyword" class="form-control" required>

                        <label class="mt-2">{{ tt('URL:') }}</label>
                        <input type="text" name="url" class="form-control" required>

                        <label class="mt-2">{{ tt('Category Picture:') }}</label>
                        <input type="file" name="picture" class="form-control" required>
                    </div>

                    <!-- Status Selection (Outside Tabs) -->
                    <label class="mt-3">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control">
                        <option value="active">{{ tt('Active') }}</option>
                        <option value="inactive">{{ tt('Inactive') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Edit Category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editCategoryTabs">
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
                                <label class="mt-2">{{ tt('Category Name:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][name]"
                                    class="form-control def_name">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $language->id }}][description]" class="form-control"
                                    maxlength="200"></textarea>
                            </div>
                        @endforeach
                    </div>

                    <!-- Common Fields for Category (in Edit Category Modal) -->
                    <div class="mt-3">
                        <label>{{ tt('Meta Keyword:') }}</label>
                        <input type="text" name="meta_keyword" class="form-control" required>

                        <label class="mt-2">{{ tt('URL:') }}</label>
                        <input type="text" name="url" class="form-control">

                        <label class="mt-2">{{ tt('Category Picture:') }}</label>
                        <input type="file" name="picture" class="form-control">
                    </div>

                    <!-- Artworks Selection -->
                    <label class="mt-2">{{ tt('Artworks:') }}</label>
                    <select name="artworks[]" class="form-control select2-artwork" multiple>
                        @foreach($artworks as $artwork)
                            <option value="{{ $artwork->id }}"
                                data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                data-artist="{{ $artwork->artist->first_name }} {{ $artwork->artist->last_name }}">
                                {{ $artwork->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Status Selection (Outside Tabs) -->
                    <label class="mt-3">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control">
                        <option value="active">{{ tt('Active') }}</option>
                        <option value="inactive">{{ tt('Inactive') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
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
                <h5 class="modal-title">{{ tt('Preview Category') }}</h5>
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
                let translationsHtml = '';
                if (category.translations && category.translations.length > 0) {
                    category.translations.forEach(translation => {
                        translationsHtml += `
                        <p>
                            <strong>${translation.language.name}:</strong> ${translation.name} <br>
                            ${translation.description ? `<em>${translation.description}</em>` : ''}
                        </p>
                    `;
                    });
                }

                let artworksHtml = '';
                if (category.artworks && category.artworks.length > 0) {
                    artworksHtml = '<h6>{{ tt('Artworks:') }}</h6>';
                    category.artworks.forEach(artwork => {
                        let imgSrc = artwork.photos ? JSON.parse(artwork.photos)[0] : '';
                        artworksHtml += `
                        <div class="d-flex align-items-center">
                            ${imgSrc ? `<img src="${window.location.origin + '/storage/' + imgSrc}" class="rounded" width="40" height="40" style="margin-right: 10px;">` : ''}
                            <div>
                                <strong>${artwork.name}</strong>
                                <small class="d-block text-muted">{{ tt('Artist:') }} ${artwork.artist.first_name} ${artwork.artist.last_name}</small>
                            </div>
                        </div>`;
                    });
                }

                // Toggle button for status
                let toggleBtn = category.status === 'active'
                    ? `<button onclick="toggleCategoryStatus(${category.id})" class="btn btn-warning">{{ tt('Deactivate') }}</button>`
                    : `<button onclick="toggleCategoryStatus(${category.id})" class="btn btn-success">{{ tt('Activate') }}</button>`;

                let previewHtml = `
                <h5><strong>{{ tt('Category Name:') }}</strong> ${category.name}</h5>
                <p><strong>{{ tt('Status:') }}</strong> ${category.status} ${toggleBtn}</p>
                <p><strong>{{ tt('Meta Keyword:') }}</strong> ${category.meta_keyword}</p>
                <p><strong>{{ tt('URL:') }}</strong> ${category.url}</p>
                <p><strong>{{ tt('Picture:') }}</strong> ${category.picture ? `<img src="${category.picture}" alt="Category Picture" style="max-width: 100px;">` : '{{ tt('No picture available') }}'}</p>
                ${translationsHtml ? '<h6>{{ tt('Translations:') }}</h6>' + translationsHtml : ''}
                ${artworksHtml}
            `;

                $('#previewCategoryModal .modal-body').html(previewHtml);
                $('#previewCategoryModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load category details.') }}');
            }
        });
    }

    function toggleCategoryStatus(categoryId) {
        $.ajax({
            url: `/dashboard/categories/${categoryId}/toggle-status`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    alert('{{ tt('Category') }} ' + (response.status === 'active' ? '{{ tt('activated') }}' : '{{ tt('deactivated') }}') + ' {{ tt('successfully.') }}');
                    // Refresh preview details
                    previewCategory(categoryId);
                }
            },
            error: function () {
                alert('{{ tt('Failed to toggle category status.') }}');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this category? This action cannot be undone.') }}")) {
            event.target.closest('form').submit();
        }
    }

    function editCategory(categoryId) {
        $.ajax({
            url: `/dashboard/categories/${categoryId}`,
            type: 'GET',
            success: function (response) {
                let category = response;
                // Populate common fields
                $('#editCategoryModal select[name="status"]').val(category.status);
                $('#editCategoryModal input[name="meta_keyword"]').val(category.meta_keyword);
                $('#editCategoryModal input[name="url"]').val(category.url);
                // $('#editCategoryModal input[name="picture"]').val(category.picture);

                // populate the select2-artwork select tag
                let selectedArtworks = category.artworks.map(art => art.id);
                $('.select2-artwork').val(selectedArtworks).trigger('change');

                // Populate translations for each language:
                // (Assuming each language tab has an input for the category name with a class "def_name"
                // and a textarea for description with a corresponding naming convention)
                category.translations.forEach(translation => {
                    // Set the category name input
                    $(`#editCategoryModal input[name="translations[${translation.language_id}][name]"]`).val(translation.name);
                    // Set the description textarea
                    $(`#editCategoryModal textarea[name="translations[${translation.language_id}][description]"]`).val(translation.description);
                });

                // Set the form action URL
                $('#editCategoryModal form').attr('action', `/dashboard/categories/${categoryId}`);
                $('#editCategoryModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load category details.') }}');
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
            alert('{{ tt('Select at least one category to delete.') }}');
        } else {
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
            console.log(selectedIds);
            if (confirm('{{ tt('Are you sure you want to delete the selected categories?') }}')) {
                this.submit();
            }
        }
    });
</script>
<script>
    $(document).ready(function () {
        function formatArtworkOption(option) {
            if (!option.id) return option.text;
            var imgSrc = $(option.element).data('img');
            var artist = $(option.element).data('artist');
            return $(` 
            <div class="d-flex align-items-center">
                ${imgSrc ? `<img src="${window.location.origin + '/storage/' + imgSrc}" class="rounded" width="40" height="40" style="margin-right: 10px;">` : ''}
                <div>
                    <strong>${option.text}</strong>
                    <small class="d-block text-muted">${artist}</small>
                </div>
            </div>
        `);
        }

        $('.select2-artwork').select2({
            placeholder: "{{ tt('Select Artworks') }}",
            templateResult: formatArtworkOption,
            templateSelection: formatArtworkOption
        });
    });
</script>

<script>
    document.querySelector('#categories').classList.add('active');
    document.querySelector('#categories .nav-link ').classList.add('active');
</script>
@endsection