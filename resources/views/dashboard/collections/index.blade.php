@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Collections</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCollectionModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <div class="d-flex justify-content-end seperate">
        <!-- Search Bar -->
        <form method="GET" action="{{ route('dashboard.collections.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['filter']))
                    <a href="{{ route('dashboard.collections.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..." placeholder="Search..."
                    required style="flex: 3;" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}">
                <select name="filter" class="form-select me-2" required>
                    <option value="id" {{ isset($_GET['filter']) && $_GET['filter'] == 'id' ? 'selected' : '' }}>ID
                    </option>
                    <option value="title" {{ isset($_GET['filter']) && $_GET['filter'] == 'title' ? 'selected' : '' }}>
                        Title
                    </option>
                </select>
            </div>
        </form>
    </div>

    <!-- Collections Table -->
    @if ($collections->isEmpty())
        <center class="alert alert-warning">No collections found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <!-- <th>ID</th> -->
                    <th>Collection Name</th>
                    <th>Tags</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collections as $collection)
                    <tr>
                        <td><input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}"></td>
                        <!-- <td>{{ $collection->id }}</td> -->
                        <td>{{ $collection->title }}</td>
                        <td>
                            @foreach(json_decode($collection->tags) as $tag_id)
                                <span class="badge bg-danger">
                                    {{ \App\Models\Tag::find($tag_id)->name }}
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

        {{ $collections->links() }}

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.collections.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">Delete Selected</button>
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
                    <h5 class="modal-title">Add Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <!-- Collection Name -->
                        <label>Collection Name:</label>
                        <input type="text" name="title" class="form-control" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <!-- Select Tags -->
                        <label class="mt-2">Tags <small>(Optional)</small></label><br>
                        <select name="tags[]" class="select2" multiple>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <!-- Select Artworks -->
                        <label class="mt-2">Artworks <small>(Optional)</small></label><br>
                        <select name="artworks[]" class="select2-artwork" multiple>
                            @foreach($artworks as $artwork)
                                <option value="{{ $artwork->id }}"
                                    data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                    data-artist="{{ $artwork->artist->first_name }} {{ $artwork->artist->last_name }}">
                                    {{ $artwork->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
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
                    <h5 class="modal-title">Edit Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Collection Name:</label>
                    <input type="text" name="title" class="form-control" required maxlength="50">

                    <label class="mt-2">Tags:</label>
                    <select name="tags[]" class="form-control select2" multiple>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>

                    <label class="mt-2">Artworks:</label>
                    <select name="artworks[]" class="form-control select2-artwork" multiple>
                        @foreach($artworks as $artwork)
                            <option value="{{ $artwork->id }}"
                                data-img="{{ $artwork->photos ? json_decode($artwork->photos)[0] : '' }}"
                                data-artist="{{ $artwork->artist->first_name }} {{ $artwork->artist->last_name }}">
                                {{ $artwork->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
                <h5 class="modal-title">Preview Collection</h5>
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
            alert('Select at least one collection to delete.');
        } else {
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
            console.log(selectedIds);
            if (confirm('Are you sure you want to delete the selected collections?')) {
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
            return $(
                `<div class="d-flex align-items-center">
                    ${imgSrc ? `<img src="${imgSrc}" class="rounded" width="40" height="40" style="margin-right: 10px;">` : ''}
                    <div>
                        <strong>${option.text}</strong>
                        <small class="d-block text-muted">${artist}</small>
                    </div>
                </div>`
            );
        }

        $('.select2').select2({
            placeholder: "Select Tags"
        });

        $('.select2-artwork').select2({
            placeholder: "Select Artworks",
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

                let artworksHtml = artworks.map(artwork => {
                    let imgSrc = artwork.photos ? JSON.parse(artwork.photos)[0] : '';
                    return `
                    <div class="d-flex align-items-center">
                        ${imgSrc ? `<img src="${imgSrc}" class="rounded" width="40" height="40" style="margin-right: 10px;">` : ''}
                        <div>
                            <strong>${artwork.name}</strong>
                            <small class="d-block text-muted">Artist: ${artwork.artist.first_name} ${artwork.artist.last_name}</small>
                        </div>
                    </div>
                `;
                }).join('');

                $('#previewCollectionModal .modal-body').html(`
                <h5><strong>Collection Name:</strong> ${collection.title}</h5>
                <p><strong>Tags:</strong> ${collection.tags.length ? collection.tags.join(', ') : 'No tags assigned'}</p>
                <h6>Artworks:</h6>
                ${artworksHtml}
            `);
                $('#previewCollectionModal').modal('show');
            },
            error: function () {
                alert('Failed to load collection details.');
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

                $('#editCollectionModal input[name="title"]').val(collection.title);
                $('#editCollectionModal form').attr('action', `/dashboard/collections/${collectionId}`);

                // Set selected tags
                let selectedTags = collection.tag_ids;
                $('.select2').val(selectedTags).trigger('change');

                // Set selected artworks
                let selectedArtworks = artworks.map(art => art.id);
                $('.select2-artwork').val(selectedArtworks).trigger('change');

                $('#editCollectionModal').modal('show');
            },
            error: function () {
                alert('Failed to load collection details.');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this collection? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }
</script>

<script>
    document.querySelector('#collections').classList.add('active');
    document.querySelector('#collections .nav-link ').classList.add('active');
</script>
@endsection