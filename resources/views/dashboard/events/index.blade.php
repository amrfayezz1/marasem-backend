@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Events</h3>
        <button data-bs-toggle="modal" data-bs-target="#addEventModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Events Table -->
    @if ($events->isEmpty())
        <center class="alert alert-warning">No events found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll"> Select
                    </th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                    <tr>
                        <td>
                            <input type="checkbox" name="event_ids[]" value="{{ $event->id }}">
                        </td>
                        <td>{{ $event->title ?? 'N/A' }}</td>
                        <td>{{ $event->date_start }} - {{ $event->date_end }}</td>
                        <td>
                            <span class="badge {{ $event->status == 'upcoming' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </td>
                        <td>
                            <span onclick="previewEvent({{ $event->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editEvent({{ $event->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.events.destroy', $event->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $events->links() }}
        <!-- Bulk Action Buttons -->
        <div class="bulks mt-3">
            <form method="POST" action="{{ route('dashboard.events.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" id="bulkDeleteBtn" class="btn btn-danger">Delete Selected</button>
            </form>

            <form method="POST" action="{{ route('dashboard.events.bulk-publish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkPublishIds">
                <button type="submit" id="bulkPublishBtn" class="btn btn-success">Publish Selected</button>
            </form>

            <form method="POST" action="{{ route('dashboard.events.bulk-unpublish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkUnpublishIds">
                <button type="submit" id="bulkUnpublishBtn" class="btn btn-warning">Unpublish Selected</button>
            </form>
        </div>
    @endif
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.events.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-2" id="eventTabs">
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
                                <label class="mt-2">Title:</label>
                                <input type="text" name="translations[{{ $loop->index }}][title]" class="form-control"
                                    required>
                                <label class="mt-2">Description:</label>
                                <textarea name="translations[{{ $loop->index }}][description]" class="form-control"
                                    required></textarea>
                                <label class="mt-2">Location:</label>
                                <input type="text" name="translations[{{ $loop->index }}][location]" class="form-control"
                                    required>
                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <label class="mt-3">Start Date:</label>
                    <input type="date" name="date_start" class="form-control" required>

                    <label class="mt-2">End Date:</label>
                    <input type="date" name="date_end" class="form-control" required>

                    <label class="mt-2">Start Time:</label>
                    <input type="time" name="time_start" class="form-control" required>

                    <label class="mt-2">End Time:</label>
                    <input type="time" name="time_end" class="form-control" required>

                    <label class="mt-2">Location URL:</label>
                    <input type="url" name="location_url" class="form-control">

                    <label class="mt-2">Cover Image:</label>
                    <input type="file" name="cover_img" class="form-control" required>

                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="upcoming">Upcoming</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-3">
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
                                <label class="mt-2">Title:</label>
                                <input type="text" name="translations[{{ $language->id }}][title]" class="form-control">
                                <label class="mt-2">Description:</label>
                                <textarea name="translations[{{ $language->id }}][description]"
                                    class="form-control"></textarea>
                                <label class="mt-2">Location:</label>
                                <input type="text" name="translations[{{ $language->id }}][location]" class="form-control">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>
                    <label>Start Date:</label>
                    <input type="date" name="date_start" class="form-control" required>

                    <label class="mt-2">End Date:</label>
                    <input type="date" name="date_end" class="form-control" required>

                    <label class="mt-2">Start Time:</label>
                    <input type="time" name="time_start" class="form-control" required>

                    <label class="mt-2">End Time:</label>
                    <input type="time" name="time_end" class="form-control" required>

                    <label class="mt-2">Location URL:</label>
                    <input type="url" name="location_url" class="form-control">

                    <label class="mt-2">Cover Image:</label>
                    <input type="file" name="cover_img" class="form-control">
                    <div class="event-cover-img mt-2"></div>

                    <label class="mt-3">Status:</label>
                    <select name="status" class="form-control">
                        <option value="upcoming">Upcoming</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Preview Event Modal -->
<div class="modal fade" id="previewEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Event</h5>
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
    function editEvent(eventId) {
        $.ajax({
            url: `/dashboard/events/${eventId}`,
            type: 'GET',
            success: function (response) {
                let event = response.event;

                // Populate event fields
                $('#editEventModal input[name="date_start"]').val(event.date_start);
                $('#editEventModal input[name="date_end"]').val(event.date_end);
                $('#editEventModal input[name="time_start"]').val(event.time_start);
                $('#editEventModal input[name="time_end"]').val(event.time_end);
                $('#editEventModal input[name="location_url"]').val(event.location_url);
                $('#editEventModal select[name="status"]').val(event.status);

                // Display current event cover image
                $('#editEventModal .event-cover-img').html(`<img src="/storage/${event.cover_img_path}" width="100%">`);

                // Populate translations
                event.translations.forEach(translation => {
                    $(`#editEventModal input[name="translations[${translation.language_id}][title]"]`).val(translation.title);
                    $(`#editEventModal textarea[name="translations[${translation.language_id}][description]"]`).val(translation.description);
                    $(`#editEventModal input[name="translations[${translation.language_id}][location]"]`).val(translation.location);
                });

                // Update form action
                $('#editEventModal form').attr('action', `/dashboard/events/${event.id}`);
                $('#editEventModal').modal('show');
            },
            error: function () {
                alert('Failed to load event details.');
            }
        });
    }
</script>
<script>
    function previewEvent(eventId) {
        $.ajax({
            url: `/dashboard/events/${eventId}`,
            type: 'GET',
            success: function (response) {
                let event = response.event;
                let translationsHtml = event.translations.map(t => `
                    <h6>${t.language ? t.language.name : ''}:</h6>
                    <p><strong>Title:</strong> ${t.title}</p>
                    <p><strong>Description:</strong> ${t.description}</p>
                    <p><strong>Location:</strong> ${t.location}</p>
                `).join('');

                $('#previewEventModal .modal-body').html(`
                    <h5><strong>${event.translations[0].title}</strong></h5>
                    <p>${event.translations[0].description}</p>
                    <p><strong>Location:</strong> ${event.translations[0].location}</p>
                    <p><strong>Date:</strong> ${event.date_start} - ${event.date_end}</p>
                    <p><strong>Time:</strong> ${event.time_start} - ${event.time_end}</p>
                    <p><strong>Status:</strong> ${event.status}</p>
                    <p><strong>Location URL:</strong> <a href="${event.location_url}" target="_blank">${event.location_url}</a></p>
                    <h6>Translations:</h6>
                    ${translationsHtml}
                    <img src="/storage/${event.cover_img_path}" width="100%">
                `);
                $('#previewEventModal').modal('show');
            },
            error: function () {
                alert('Failed to load event details.');
            }
        });
    }
</script>
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this event? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }
</script>
<script>
    function getSelectedEventIds() {
        return Array.from(document.querySelectorAll('input[name="event_ids[]"]:checked'))
            .map(cb => cb.value);
    }

    function updateBulkActionInput(fieldId) {
        const selectedIds = getSelectedEventIds();
        if (selectedIds.length === 0) {
            alert('Select at least one event.');
            return false;
        }
        document.getElementById(fieldId).value = JSON.stringify(selectedIds);
        return true;
    }

    document.getElementById('bulkDeleteBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkDeleteIds')) {
            event.preventDefault();
        }
    });

    document.getElementById('bulkPublishBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkPublishIds')) {
            event.preventDefault();
        }
    });

    document.getElementById('bulkUnpublishBtn').addEventListener('click', function (event) {
        if (!updateBulkActionInput('bulkUnpublishIds')) {
            event.preventDefault();
        }
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="event_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
</script>

<script>
    document.querySelector('#events').classList.add('active');
    document.querySelector('#events .nav-link ').classList.add('active');
</script>
@endsection