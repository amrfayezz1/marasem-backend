@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Collections') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCollectionModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search Bar with Rows Per Page -->
        <form method="GET" action="{{ route('dashboard.collections.index') }}" class="mb-3 d-flex align-items-center">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['filter']))
                    <a href="{{ route('dashboard.collections.index') }}"
                        class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..."
                    placeholder="{{ tt('Search...') }}" required style="flex: 3;" value="{{ request('search', '') }}">
                <select name="filter" class="form-select me-2" required>
                    <option value=""></option>
                    <option value="id" {{ request('filter') == 'id' ? 'selected' : '' }}>{{ tt('ID') }}</option>
                    <option value="title" {{ request('filter') == 'title' ? 'selected' : '' }}>{{ tt('Title') }}</option>
                </select>
            </div>
            <div class="">
                <select name="rows" class="form-select" onchange="this.form.submit()">
                    <option value="10" {{ request('rows', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('rows') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('rows') == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Collections Table -->
    @if ($collections->isEmpty())
        <center class="alert alert-warning">{{ tt('No collections found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('ID') }}</th>
                    <th>{{ tt('Collection Name') }}</th>
                    <th>{{ tt('Tags') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $collection)
                        <tr>
                            <td><input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}"></td>
                            <td>{{ $collection->id }}</td>
                            <td>
                                @php
                                    // Get the translation for the collection title based on the preferred language
                                    $translation = $collection->translations->firstWhere('language_id', $preferredLanguage);
                                @endphp
                                {{ $translation ? $translation->title : $collection->title }}
                            </td>
                            <td>
                                @foreach(json_decode($collection->tags) as $tag_id)
                                            @php
                                                $tag = \App\Models\Tag::find($tag_id);
                                                $tagTranslation = $tag ? $tag->translations->firstWhere('language_id', $preferredLanguage) : null;
                                            @endphp
                                            <span class="badge bg-danger">
                                                {{ $tagTranslation ? $tagTranslation->name : $tag->name }}
                                            </span>
                                @endforeach
                            </td>
                            <td>
                                <span onclick="previewCollection({{ $collection->id }})"><i class="fa-solid fa-eye"></i></span>
                                <span onclick="editCollection({{ $collection->id }})"><i
                                        class="fa-solid fa-pen-to-square"></i></span>
                                <form action="{{ route('dashboard.collections.destroy', $collection->id) }}" method="POST"
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

        @if ($collections->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$collections->onFirstPage())
                        <a href="{{ $collections->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $collections->lastPage(); $i++)
                        <a href="{{ $collections->url($i) }}">
                            <li class="page-item {{ $i == $collections->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($collections->hasMorePages())
                        <a href="{{ $collections->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.collections.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">{{ tt('Delete Selected') }}</button>
        </form>
    @endif
</div>

<!-- Add Collection Modal -->
<div class="modal fade" id="addCollectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.collections.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Add Collection') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="collectionTabs">
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
                                <label class="mt-2">{{ tt('Collection Name:') }}</label>
                                <input type="text" name="translations[{{ $loop->index }}][title]" class="form-control"
                                    required>

                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $loop->index }}][description]" class="form-control"
                                    maxlength="200"></textarea>

                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <label class="mt-2">{{ tt('Tags:') }}</label>
                    <select name="tags[]" class="form-control select2" multiple>
                        @foreach($tags as $tag)
                                                @php
                                                    $tagTranslation = $tag ? $tag->translations->firstWhere('language_id', $preferredLanguage) : null;
                                                @endphp
                                                <option value="{{ $tag->id }}">{{ $tagTranslation->name }}</option>
                        @endforeach
                    </select>

                    <label class="mt-2">{{ tt('Artworks:') }}</label>
                    <select name="artworks[]" class="form-control select2-artwork" multiple>
                        @foreach($artworks as $artwork)
                                                @php
                                                    $artworkTranslation = $artwork ? $artwork->translations->firstWhere('language_id', $preferredLanguage) : null;
                                                    $artistTranslation = $artwork->artist->translations->firstWhere('language_id', $preferredLanguage);
                                                @endphp
                                                <option value="{{ $artwork->id }} "
                                                    data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                                    data-artist="{{ $artistTranslation->first_name }} {{ $artistTranslation->last_name }}">
                                                    {{ $artworkTranslation->name }}
                                                </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Collection Modal -->
<div class="modal fade" id="editCollectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Edit Collection') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editCollectionTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label class="mt-2">{{ tt('Collection Name:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][title]"
                                    class="form-control def_title">

                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $language->id }}][description]"
                                    class="form-control def_desc" maxlength="200"></textarea>
                            </div>
                        @endforeach
                    </div>

                    <label class="mt-2">{{ tt('Tags:') }}</label>
                    <select name="tags[]" class="form-control select2" multiple>
                        @foreach($tags as $tag)
                                                @php
                                                    $tagTranslation = $tag ? $tag->translations->firstWhere('language_id', $preferredLanguage) : null;
                                                @endphp
                                                <option value="{{ $tag->id }}">{{ $tagTranslation->name }}</option>
                        @endforeach
                    </select>

                    <label class="mt-2">{{ tt('Artworks:') }}</label>
                    <select name="artworks[]" class="form-control select2-artwork" multiple>
                        @foreach($artworks as $artwork)
                                                @php
                                                    $artworkTranslation = $artwork ? $artwork->translations->firstWhere('language_id', $preferredLanguage) : null;
                                                    $artistTranslation = $artwork->artist->translations->firstWhere('language_id', $preferredLanguage);
                                                @endphp
                                                <option value="{{ $artwork->id }} "
                                                    data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                                    data-artist="{{ $artistTranslation->first_name }} {{ $artistTranslation->last_name }}">
                                                    {{ $artworkTranslation->name }}
                                                </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Collection Modal -->
<div class="modal fade" id="previewCollectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ tt('Preview Collection') }}</h5>
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
<!-- Scripts -->
<script>
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="collection_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('bulkDeleteForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="collection_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            e.preventDefault();
            alert('{{ tt('Select at least one collection to delete.') }}');
        } else {
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
            console.log(selectedIds);
            if (confirm('{{ tt('Are you sure you want to delete the selected collections?') }}')) {
                this.submit();
            }
        }
    });
</script>
<!-- select tags -->
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
                </div>`);
        }

        $('.select2-artwork').select2({
            placeholder: "{{ tt('Select Artworks') }}",
            templateResult: formatArtworkOption,
            templateSelection: formatArtworkOption
        });
    });
</script>
<!-- actions -->
<script>
    function previewCollection(collectionId) {
        $.ajax({
            url: `/dashboard/collections/${collectionId}`,
            type: 'GET',
            success: function (response) {
                let collection = response.collection;
                let artworks = response.artworks;

                // Build translations HTML
                let translationsHtml = collection.translations.map(translation => `
                <p><strong>${translation.language.name}:</strong> <br>
                <strong>{{ tt('Title:') }}</strong> ${translation.title} <br>
                <strong>{{ tt('Description:') }}</strong> ${translation.description ? `<em>${translation.description}</em>` : ''}</p>
            `).join('');

                // Build artworks HTML
                let artworksHtml = artworks.map(artwork => {
                    let imgSrc = artwork.photos ? JSON.parse(artwork.photos)[0] : '';
                    return `
                    <div class="d-flex align-items-center">
                        ${imgSrc ? `<img src="${window.location.origin + '/storage/' + imgSrc}" class="rounded" width="40" height="40" style="margin-right: 10px;">` : ''}
                        <div>
                            <strong>${artwork.name}</strong>
                            <small class="d-block text-muted">{{ tt('Artist:') }} ${artwork.artist.first_name} ${artwork.artist.last_name}</small>
                        </div>
                    </div>`;
                }).join('');

                // Toggle button for active/deactivate
                let activeBtn = collection.active
                    ? `<button onclick="toggleActive(${collection.id})" class="btn btn-warning">{{ tt('Deactivate') }}</button>`
                    : `<button onclick="toggleActive(${collection.id})" class="btn btn-success">{{ tt('Activate') }}</button>`;

                $('#previewCollectionModal .modal-body').html(`
                <h5><strong>{{ tt('Collection Name:') }}</strong> ${collection.title}</h5>
                <p><strong>{{ tt('Description:') }}</strong> ${collection.description ? collection.description : '{{ tt('No description provided.') }}'}</p>
                <div class="mb-3">${activeBtn}</div>
                <h6>{{ tt('Translations:') }}</h6>
                ${translationsHtml}
                <p><strong>{{ tt('Tags:') }}</strong> ${collection.tags.length ? collection.tags.join(', ') : '{{ tt('No tags assigned') }}'}</p>
                <h6>{{ tt('Artworks:') }}</h6>
                ${artworksHtml}`);
                $('#previewCollectionModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load collection details.') }}');
            }
        });
    }

    function toggleActive(collectionId) {
        $.ajax({
            url: `/dashboard/collections/${collectionId}/toggle-active`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    alert('{{ tt('Collection') }} ' + (response.active ? '{{ tt('activated') }}' : '{{ tt('deactivated') }}') + ' {{ tt('successfully.') }}');
                    // Refresh the preview
                    previewCollection(collectionId);
                }
            },
            error: function () {
                alert('{{ tt('Failed to toggle collection status.') }}');
            }
        });
    }

    function editCollection(collectionId) {
        $.ajax({
            url: `/dashboard/collections/${collectionId}`,
            type: 'GET',
            success: function (response) {
                let collection = response.collection;
                let artworks = response.artworks;
                // Update form action
                $('#editCollectionModal form').attr('action', `/dashboard/collections/${collectionId}`);

                // Populate translations for collection name
                $('.def_title').val(collection.title); // Default language
                $('.def_desc').val(collection.description); // Default language
                collection.translations.forEach((translation, i) => {
                    $(`#editCollectionModal input[name="translations[${translation.language_id}][title]"]`).val(translation.title);
                    document.querySelectorAll(".def_desc")[i].value = translation.description;
                });

                // Set selected tags
                let selectedTags = collection.tag_ids || [];
                $('.select2').val(selectedTags).trigger('change');

                // Set selected artworks
                let selectedArtworks = artworks.map(art => art.id);
                $('.select2-artwork').val(selectedArtworks).trigger('change');

                // Show the modal
                $('#editCollectionModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load collection details.') }}');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this collection? This action cannot be undone.') }}")) {
            event.target.closest('form').submit();
        }
    }
</script>

<script>
    document.querySelector('#collections').classList.add('active');
    document.querySelector('#collections .nav-link ').classList.add('active');
</script>
@endsection