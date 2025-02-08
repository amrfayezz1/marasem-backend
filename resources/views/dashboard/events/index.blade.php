@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Events') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addEventModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Events Table -->
    @if ($events->isEmpty())
        <center class="alert alert-warning">{{ tt('No events found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll"> {{ tt('Select') }}
                    </th>
                    <th>{{ tt('Title') }}</th>
                    <th>{{ tt('Date') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                    <tr>
                        <td>
                            <input type="checkbox" name="event_ids[]" value="{{ $event->id }}">
                        </td>
                        <td>{{ $event->title ?? tt('N/A') }}</td>
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


        @if ($events->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$events->onFirstPage())
                        <a href="{{ $events->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $events->lastPage(); $i++)
                        <a href="{{ $events->url($i) }}">
                            <li class="page-item {{ $i == $events->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif
        <!-- Bulk Action Buttons -->
        <div class="bulks mt-3">
            <form method="POST" action="{{ route('dashboard.events.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" id="bulkDeleteBtn" class="btn btn-danger">{{ tt('Delete Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.events.bulk-publish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkPublishIds">
                <button type="submit" id="bulkPublishBtn" class="btn btn-success">{{ tt('Publish Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.events.bulk-unpublish') }}" class="d-inline">
                @csrf
                <input type="hidden" name="ids" id="bulkUnpublishIds">
                <button type="submit" id="bulkUnpublishBtn" class="btn btn-warning">{{ tt('Unpublish Selected') }}</button>
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
                    <h5 class="modal-title">{{ tt('Add Event') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-2" id="eventTabs">
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
                                <label class="mt-2">{{ tt('Title:') }}</label>
                                <input type="text" name="translations[{{ $loop->index }}][title]" class="form-control"
                                    required>
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $loop->index }}][description]" class="form-control"
                                    required></textarea>
                                <label class="mt-2">{{ tt('Location:') }}</label>
                                <input type="text" name="translations[{{ $loop->index }}][location]" class="form-control"
                                    required>
                                <input type="hidden" name="translations[{{ $loop->index }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>

                    <label class="mt-3">{{ tt('Start Date:') }}</label>
                    <input type="date" name="date_start" class="form-control" required>

                    <label class="mt-2">{{ tt('End Date:') }}</label>
                    <input type="date" name="date_end" class="form-control" required>

                    <label class="mt-2">{{ tt('Start Time:') }}</label>
                    <input type="time" name="time_start" class="form-control" required>

                    <label class="mt-2">{{ tt('End Time:') }}</label>
                    <input type="time" name="time_end" class="form-control" required>

                    <label class="mt-2">{{ tt('Location URL:') }}</label>
                    <input type="url" name="location_url" class="form-control">

                    <label class="mt-2">{{ tt('Cover Image:') }}</label>
                    <input type="file" name="cover_img" class="form-control" required>

                    <label class="mt-3">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control">
                        <option value="upcoming">{{ tt('Upcoming') }}</option>
                        <option value="ended">{{ tt('Ended') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
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
                    <h5 class="modal-title">{{ tt('Edit Event') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mt-3">
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
                                <label class="mt-2">{{ tt('Title:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][title]" class="form-control">
                                <label class="mt-2">{{ tt('Description:') }}</label>
                                <textarea name="translations[{{ $language->id }}][description]"
                                    class="form-control"></textarea>
                                <label class="mt-2">{{ tt('Location:') }}</label>
                                <input type="text" name="translations[{{ $language->id }}][location]" class="form-control">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                            </div>
                        @endforeach
                    </div>
                    <label>{{ tt('Start Date:') }}</label>
                    <input type="date" name="date_start" class="form-control" required>

                    <label class="mt-2">{{ tt('End Date:') }}</label>
                    <input type="date" name="date_end" class="form-control" required>

                    <label class="mt-2">{{ tt('Start Time:') }}</label>
                    <input type="time" name="time_start" class="form-control" required>

                    <label class="mt-2">{{ tt('End Time:') }}</label>
                    <input type="time" name="time_end" class="form-control" required>

                    <label class="mt-2">{{ tt('Location URL:') }}</label>
                    <input type="url" name="location_url" class="form-control">

                    <label class="mt-2">{{ tt('Cover Image:') }}</label>
                    <input type="file" name="cover_img" class="form-control">
                    <div class="event-cover-img mt-2"></div>

                    <label class="mt-3">{{ tt('Status:') }}</label>
                    <select name="status" class="form-control">
                        <option value="upcoming">{{ tt('Upcoming') }}</option>
                        <option value="ended">{{ tt('Ended') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
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
                <h5 class="modal-title">{{ tt('Preview Event') }}</h5>
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
                alert('{{ tt('Failed to load event details.') }}');
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
                    <p><strong>{{ tt('Title:') }}</strong> ${t.title}</p>
                    <p><strong>{{ tt('Description:') }}</strong> ${t.description}</p>
                    <p><strong>{{ tt('Location:') }}</strong> ${t.location}</p>
                `).join('');

                $('#previewEventModal .modal-body').html(`
                    <h5><strong>${event.translations[0].title}</strong></h5>
                    <p>${event.translations[0].description}</p>
                    <p><strong>{{ tt('Location:') }}</strong> ${event.translations[0].location}</p>
                    <p><strong>{{ tt('Date:') }}</strong> ${event.date_start} - ${event.date_end}</p>
                    <p><strong>{{ tt('Time:') }}</strong> ${event.time_start} - ${event.time_end}</p>
                    <p><strong>{{ tt('Status:') }}</strong> ${event.status}</p>
                    <p><strong>{{ tt('Location URL:') }}</strong> <a href="${event.location_url}" target="_blank">${event.location_url}</a></p>
                    <h6>{{ tt('Translations:') }}</h6>
                    ${translationsHtml}
                    <img src="/storage/${event.cover_img_path}" width="100%">
                `);
                $('#previewEventModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load event details.') }}');
            }
        });
    }
</script>
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this event? This action cannot be undone.') }}")) {
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
            alert('{{ tt('Select at least one event.') }}');
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