@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Artworks') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#artworkModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end separate">
        <form method="GET" action="{{ route('dashboard.artworks.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['status']))
                    <a href="{{ route('dashboard.artworks.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="{{ tt('Search by Title or Tag') }}"
                    value="{{ request('search', '') }}">

                <select name="status" class="form-select">
                    <option value="all">{{ tt('All Statuses') }}</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>{{ tt('Need to be reviewed') }}
                    </option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>{{ tt('Reviewed') }}</option>
                </select>

                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Artworks Table -->
    @if ($artworks->isEmpty())
        <center class="alert alert-warning">{{ tt('No artworks found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('ID') }}</th>
                    <th>{{ tt('Image') }}</th>
                    <th>{{ tt('Title') }}</th>
                    <th>{{ tt('Collections') }}</th>
                    <th>{{ tt('Subcategories') }}</th>
                    <th>{{ tt('Artist') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($artworks as $artwork)
                    <tr>
                        <td><input type="checkbox" name="artwork_ids[]" value="{{ $artwork->id }}"></td>
                        <td>{{ $artwork->id }}</td>
                        <td>
                            @if (json_decode($artwork->photos))
                                <img src="{{ asset('storage/' . json_decode($artwork->photos)[0]) }}" width="50" height="50"
                                    class="rounded">
                            @else
                                <span class="text-muted">{{ tt('N/A') }}</span>
                            @endif
                        </td>
                        <td>{{ $artwork->name }}</td>
                        <td>
                            @if ($artwork->collections->isEmpty())
                                <span class="text-muted">{{ tt('N/A') }}</span>
                            @else
                                @foreach($artwork->collections as $collection)
                                    <span class="badge bg-warning">{{ $collection->title }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            @foreach($artwork->tags as $tag)
                                <span class="badge bg-secondary">{{ $tag->name }}</span>
                            @endforeach
                        </td>
                        <td>{{ $artwork->artist->first_name }} {{ $artwork->artist->last_name }}</td>
                        <td>
                            <span class="badge {{ $artwork->reviewed == '1' ? 'bg-success' : 'bg-danger' }}">
                                {{ $artwork->reviewed == '1' ? tt('Reviewed') : tt('Not Reviewed') }}
                            </span>
                        </td>
                        <td>
                            <span onclick="previewArtwork({{ $artwork->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editArtwork({{ $artwork->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.artworks.destroy', $artwork->id) }}" method="POST"
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

        @if ($artworks->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if (!$artworks->onFirstPage())
                        <a href="{{ $artworks->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif

                    @php
                        $total = $artworks->lastPage();
                        $current = $artworks->currentPage();
                        // Calculate start and end page numbers to display
                        $start = max($current - 2, 1);
                        $end = min($start + 4, $total);
                        // Adjust start if we are near the end to ensure we show 5 pages if possible
                        $start = max($end - 4, 1);
                    @endphp

                    @for ($i = $start; $i <= $end; $i++)
                        <a href="{{ $artworks->url($i) }}">
                            <li class="page-item {{ $i == $current ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor

                    {{-- Next Page Link --}}
                    @if ($artworks->hasMorePages())
                        <a href="{{ $artworks->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.artworks.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">{{ tt('Delete Selected') }}</button>
        </form>
    @endif
</div>

<!-- Preview Artwork Modal -->
<div class="modal fade" id="previewArtworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ tt('Artwork Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Artwork Modal -->
<div class="modal fade" id="artworkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="artworkModalTitle">{{ tt('Add Artwork') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="languageTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label>{{ tt('Title') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][name]"
                                    class="form-control lang-name" required>
                                <label>{{ tt('Art Type') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][art_type]"
                                    class="form-control lang-type">
                                <label>{{ tt('Description') }}:</label>
                                <textarea name="translations[{{ $language->id }}][description]"
                                    class="form-control lang-desc"></textarea>
                            </div>
                        @endforeach
                    </div>

                    <!-- Other Fields -->
                    <label class="mt-3">{{ tt('Artist') }}:</label>
                    <select name="artist_id" class="form-control select2-artist">
                        @foreach($artists as $artist)
                            <option value="{{ $artist->id }}"
                                data-ar="{{ $artist->translations->firstWhere('language_id', 2)->first_name ?? '' }} {{ $artist->translations->firstWhere('language_id', 2)->last_name ?? '' }}"
                                data-email="{{ $artist->email }}" data-id="{{ $artist->id }}"
                                data-profile="{{ $artist->profile_picture ? asset('storage/' . $artist->profile_picture) : '' }}">
                                {{ $artist->first_name }} {{ $artist->last_name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="mt-2">{{ tt('Collections') }}:</label>
                    <select name="collections[]" class="form-control select2-collection" multiple>
                        @foreach($collections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                        @endforeach
                    </select>

                    <label class="mt-2">{{ tt('Subcategories') }}:</label>
                    <select name="subcategories[]" class="form-control select2-tags" multiple>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>

                    <label class="mt-2">{{ tt('Sizes & Prices') }}:</label>
                    <div id="sizePriceContainer">
                        <div class="input-group mb-2 size-price-entry">
                            <input type="text" name="sizes_prices[0][size]" class="form-control"
                                placeholder="{{ tt('Size (e.g., M, L, XL)') }}" required>
                            <input type="number" name="sizes_prices[0][price]" class="form-control"
                                placeholder="{{ tt('Price (e.g., 50)') }}" required>
                            <button type="button" class="btn btn-danger remove-size-price"
                                onclick="removeSizePrice(this)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary mt-2" id="addSizePrice">
                        {{ tt('Add Size & Price') }}
                    </button>

                    <label class="mt-2">{{ tt('Artwork Image') }}:</label>
                    <input type="file" name="photos" class="form-control">

                    <label class="mt-2">{{ tt('Artwork Status') }}:</label>
                    <select name="artwork_status" id="artwork_status" class="form-control">
                        <option value="ready_to_ship">{{ tt('ready to ship') }}</option>
                        <option value="customized_only">{{ tt('customized only') }}</option>
                        <option value="both">{{ tt('both') }}</option>
                    </select>

                    <label class="mt-2">{{ tt('Reviewed Status') }}:</label>
                    <select name="reviewed" class="form-control" id="reviewed">
                        <option value="0">{{ tt('Need to be reviewed') }}</option>
                        <option value="1">{{ tt('Reviewed') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.getElementById('bulkDeleteForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="artwork_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            e.preventDefault();
            alert('{{ tt('Select at least one artwork to delete.') }}');
        } else {
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
            console.log(selectedIds);
            if (confirm('{{ tt('Are you sure you want to delete the selected artworks?') }}')) {
                this.submit();
            }
        }
    });

    function editArtwork(artworkId) {
        $.ajax({
            url: `/dashboard/artworks/${artworkId}`,
            type: 'GET',
            success: function (response) {
                let artwork = response.artwork;

                // Update Modal Title & Form Action
                $('#artworkModalTitle').text('{{ tt('Edit Artwork') }}');
                $('#artworkModal form').attr('action', `/dashboard/artworks/${artworkId}`);
                $('#artworkModal form').append('<input type="hidden" name="_method" value="PUT">');

                // Populate Default Fields
                $('.lang-name').val(artwork.name);
                $('.lang-type').val(artwork.art_type);
                $('.lang-desc').val(artwork.description);

                // Populate Translations
                artwork.translations.forEach(translation => {
                    $(`#artworkModal input[name="translations[${translation.language_id}][name]"]`).val(translation.name);
                    $(`#artworkModal input[name="translations[${translation.language_id}][art_type]"]`).val(translation.art_type);
                    $(`#artworkModal textarea[name="translations[${translation.language_id}][description]"]`).val(translation.description);
                });

                // Populate Other Fields
                $('#artworkModal select[name="artist_id"]').val(artwork.artist_id).trigger('change');
                $('#artworkModal select[name="reviewed"]').val(artwork.reviewed).trigger('change');
                $('#artworkModal select[name="artwork_status"]').val(artwork.artwork_status).trigger('change');
                $('#artworkModal input[name="price"]').val(artwork.price);

                // Populate Collections & Subcategories
                let selectedCollections = artwork.collections.map(collection => collection.id);
                $('#artworkModal select[name="collections[]"]').val(selectedCollections).trigger('change');

                let subcategoryIds = artwork.tags.map(tag => tag.id);
                $('#artworkModal select[name="subcategories[]"]').val(subcategoryIds).trigger('change');

                // Populate Sizes & Prices
                $('#sizePriceContainer').empty(); // Clear existing inputs
                let sizesPrices = JSON.parse(artwork.sizes_prices);
                Object.entries(sizesPrices).forEach(([size, price], index) => {
                    $('#sizePriceContainer').append(`
                        <div class="input-group mb-2 size-price-entry">
                            <input type="text" name="sizes_prices[${index}][size]" class="form-control" value="${size}" required>
                            <input type="number" name="sizes_prices[${index}][price]" class="form-control" value="${price}" required>
                            <button type="button" class="btn btn-danger remove-size-price" onclick="removeSizePrice(this)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `);
                });

                // Show the modal
                $('#artworkModal').modal('show');
            },
            error: function () {
                alert('Failed to load artwork details.');
            }
        });
    }
</script>
<!-- size & prize -->
<script>
    $(document).ready(function () {
        let sizePriceIndex = 1; // Track number of added fields

        // Add new size & price input group
        $('#addSizePrice').click(function () {
            $('#sizePriceContainer').append(`
                <div class="input-group mb-2 size-price-entry">
                    <input type="text" name="sizes_prices[${sizePriceIndex}][size]" class="form-control" placeholder="{{ tt('Size (e.g., M, L, XL)') }}" required>
                    <input type="number" name="sizes_prices[${sizePriceIndex}][price]" class="form-control" placeholder="{{ tt('Price (e.g., 50)') }}" required>
                    <button type="button" class="btn btn-danger remove-size-price" onclick="removeSizePrice(this)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `);
            sizePriceIndex++;
        });

        // Remove size-price entry
        window.removeSizePrice = function (element) {
            $(element).closest('.size-price-entry').remove();
        };
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="artwork_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
</script>
<!-- preview -->
<script>
    function previewArtwork(artworkId) {
        // Show loading text in the modal body
        $('#previewArtworkModal .modal-body').html('Loading...');

        $.ajax({
            url: '/dashboard/artworks/' + artworkId, // Adjust if needed
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                var artwork = response.artwork;
                var html = '<div>';

                // Header with basic artwork info
                html += '<h3>' + artwork.name + ' (ID: ' + artwork.id + ')</h3>';

                // Artist details
                if (artwork.artist) {
                    html += '<p><strong>Artist:</strong> '
                        + artwork.artist.first_name + ' '
                        + artwork.artist.last_name;
                    if (artwork.artist.email) {
                        html += ' (' + artwork.artist.email + ')';
                    }
                    html += '</p>';
                }

                // Artwork photos (if any)
                if (artwork.photos) {
                    // Try parsing photos if it's stored as a JSON string.
                    var photos = artwork.photos;
                    if (typeof photos === 'string') {
                        try {
                            photos = JSON.parse(photos);
                        } catch (e) {
                            photos = [photos];
                        }
                    }
                    if (Array.isArray(photos)) {
                        html += '<div><strong>Photos:</strong><br/>';
                        photos.forEach(function (photo) {
                            html += '<img src="' + window.location.origin + '/storage/' + photo + '" style="max-width:100px; margin:5px;" />';
                        });
                        html += '</div>';
                    }
                }
                // Basic artwork information in a definition list
                html += '<dl>';
                html += '<dt>{{ tt('Art Type') }}:</dt><dd>' + artwork.art_type + '</dd>';
                html += '<dt>{{ tt('Artwork Status') }}:</dt><dd>' + artwork.artwork_status + '</dd>';
                html += '<dt>{{ tt('Sizes & Prices') }}:</dt>';
                html += '<dd>';
                if (artwork.sizes_prices) {
                    var sizesPrices = JSON.parse(artwork.sizes_prices);
                    Object.entries(sizesPrices).forEach(([size, price]) => {
                        html += size + ': ' + price + '<br/>';
                    });
                }
                html += '</dd>';
                html += '<dt>{{tt('Description')}}:</dt><dd>' + artwork.description + '</dd>';
                html += '<dt>{{tt('Customizable')}}:</dt><dd>' + (artwork.customizable ? '{{ tt('Yes') }}' : '{{ tt('No') }}') + '</dd>';
                // html += '<dt>{{tt('Duration')}}:</dt><dd>' + artwork.duration + '</dd>';
                html += '<dt>{{tt('Likes Count')}}:</dt><dd>' + artwork.likes_count + '</dd>';
                html += '<dt>{{tt('Min Price')}}:</dt><dd>' + artwork.min_price + '</dd>';
                html += '<dt>{{tt('Max Price')}}:</dt><dd>' + artwork.max_price + '</dd>';
                html += '<dt>{{ tt('Reviewed') }}:</dt><dd>' + (artwork.reviewed ? '{{ tt('Yes') }}' : '{{ tt('No') }}') + '</dd>';
                html += '</dl>';

                // Collections (if any)
                if (artwork.collections && artwork.collections.length > 0) {
                    html += '<h4>{{ tt('Collections') }}</h4><ul>';
                    artwork.collections.forEach(function (collection) {
                        html += '<li>' + collection.title + ' (ID: ' + collection.id + ')</li>';
                    });
                    html += '</ul>';
                }

                // Tags (if any)
                if (artwork.tags && artwork.tags.length > 0) {
                    html += '<h4>{{ tt('Tags') }}</h4><ul>';
                    artwork.tags.forEach(function (tag) {
                        html += '<li>' + tag.name + ' (ID: ' + tag.id + ')</li>';
                    });
                    html += '</ul>';
                }

                // Artwork translations in all languages
                if (artwork.translations && artwork.translations.length > 0) {
                    html += '<h4>{{ tt('Translations') }}</h4>';
                    html += '<table class="table table-bordered">';
                    html += '<thead><tr><th>{{ tt('Language') }}</th><th>{{ tt('Name') }}</th><th>{{ tt('Art Type') }}</th><th>{{ tt('Description') }}</th></tr></thead><tbody>';
                    artwork.translations.forEach(function (translation) {
                        html += '<tr>';
                        html += '<td>' + translation.language.name + '</td>';
                        html += '<td>' + translation.name + '</td>';
                        html += '<td>' + translation.art_type + '</td>';
                        html += '<td>' + translation.description + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }

                html += '</div>';

                // Insert the HTML into the modal body and show the modal
                $('#previewArtworkModal .modal-body').html(html);
                $('#previewArtworkModal').modal('show');
            },
            error: function (xhr, status, error) {
                $('#previewArtworkModal .modal-body').html("{{ tt('Failed to load artwork details.') }}");
                $('#previewArtworkModal').modal('show');
            }
        });
    }
</script>

<!-- side bar -->
<script>
    document.querySelector('#artworks').classList.add('active');
    document.querySelector('#artworks .nav-link ').classList.add('active');
</script>
<!-- select 2 -->
<script>
    $(document).ready(function () {
        function formatArtist(artist) {
            if (!artist.id) {
                return artist.text;
            }

            // Get data attributes from the option element.
            var $element = $(artist.element);
            var profilePic = $element.data('profile');
            var email = $element.data('email');
            var id = $element.data('id');
            var name = artist.text; // The text from the option

            // Build the HTML markup. If a profile picture exists, include an image tag.
            var html = '<span style="display:flex; align-items: center;">';
            if (profilePic) {
                html += '<img src="' + profilePic + '" style="width:30px; height:30px; border-radius:50%; margin-right:8px;" />';
            }
            html += '<span>';
            html += '<strong>' + name + '</strong>';
            html += '<small>(' + email + ') [ID: ' + id + ']</small>';
            html += '</span>';
            html += '</span>';

            return html;
        }

        $('.select2-artist').select2({
            templateResult: formatArtist,
            templateSelection: formatArtist,
            escapeMarkup: function (m) { return m; } // Allow HTML in the output.
        });
    });
</script>
@endsection