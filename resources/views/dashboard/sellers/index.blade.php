@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Seller List') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addSellerModal" class="btn btn-primary">
            {{ tt('Add Seller') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.sellers.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['status']))
                    <a href="{{ route('dashboard.sellers.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="{{ tt('Search by Name or ID') }}"
                    value="{{ request('search', '') }}">

                <select name="status" class="form-select">
                    <option value="all">{{ tt('All Statuses') }}</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ tt('Approved') }}
                    </option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ tt('Pending') }}
                    </option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ tt('Rejected') }}
                    </option>
                </select>

                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Seller Table -->
    @if ($sellers->isEmpty())
        <center class="alert alert-warning">{{ tt('No sellers found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('ID') }}</th>
                    <th>{{ tt('Name') }}</th>
                    <th>{{ tt('Profile Picture') }}</th>
                    <th>{{ tt('Registration Date') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sellers as $seller)
                    <tr>
                        <td><input type="checkbox" name="seller_ids[]" value="{{ $seller->id }}"></td>
                        <td>{{ $seller->id }}</td>
                        <td>{{ $seller->first_name }} {{ $seller->last_name }}</td>
                        <td>
                            @if ($seller->profile_picture)
                                <img src="{{ asset('storage/' . $seller->profile_picture) }}" width="50" height="50"
                                    class="rounded">
                            @else
                                <span>{{ tt('N/A') }}</span>
                            @endif
                        </td>
                        <td>{{ $seller->created_at->format('Y-m-d') }}</td>
                        <td>
                            <span
                                class="badge {{ $seller->artistDetails->status == 'approved' ? 'bg-success' : ($seller->artistDetails->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                {{ ucfirst(tt($seller->artistDetails->status)) }}
                            </span>
                        </td>
                        <td>
                            <span onclick="viewSeller({{ $seller->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editSeller({{ $seller->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.sellers.destroy', $seller->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        
        @if ($sellers->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if (!$sellers->onFirstPage())
                        <a href="{{ $sellers->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif

                    @php
                        $total = $sellers->lastPage();
                        $current = $sellers->currentPage();
                        // Calculate start and end page numbers to display
                        $start = max($current - 2, 1);
                        $end = min($start + 4, $total);
                        // Adjust start if we are near the end to ensure we show 5 pages if possible
                        $start = max($end - 4, 1);
                    @endphp

                    @for ($i = $start; $i <= $end; $i++)
                        <a href="{{ $sellers->url($i) }}">
                            <li class="page-item {{ $i == $current ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor

                    {{-- Next Page Link --}}
                    @if ($sellers->hasMorePages())
                        <a href="{{ $sellers->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Actions -->
        <div class="bulks mt-3">
            <form method="POST" action="{{ route('dashboard.sellers.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-danger">{{ tt('Delete Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.sellers.bulk-update-status') }}" class="d-inline"
                id="bulkUpdateForm">
                @csrf
                <div class="input-group">
                    <select name="status" class="form-select w-auto">
                        <option value="approved">{{ tt('Approve') }}</option>
                        <option value="pending">{{ tt('Mark as Pending') }}</option>
                        <option value="rejected">{{ tt('Reject') }}</option>
                    </select>
                    <input type="hidden" name="ids" id="bulkUpdateStatusIds">
                    <button type="submit" class="btn btn-primary">{{ tt('Update Status') }}</button>
                </div>
            </form>
        </div>
    @endif
</div>

<!-- Add Seller Modal -->
<div class="modal fade" id="addSellerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.sellers.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Add Seller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="sellerTabs">
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
                                <label>{{ tt('First Name') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]"
                                    class="form-control lang-fname" required>

                                <label class="mt-2">{{ tt('Last Name') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]"
                                    class="form-control lang-lname" required>
                            </div>
                        @endforeach
                    </div>

                    <!-- Other Fields -->
                    <label class="mt-2">{{ tt('Email') }}:</label>
                    <input type="email" name="email" class="form-control" required>

                    <label class="mt-2">{{ tt('Country Code') }}:</label>
                    <input type="text" name="country_code" class="form-control" value="+20" required>

                    <label class="mt-2">{{ tt('Phone') }}:</label>
                    <input type="text" name="phone" class="form-control" required>

                    <label class="mt-2">{{ tt('Profile Picture') }}:</label>
                    <input type="file" name="profile_picture" class="form-control">

                    <label class="mt-2">{{ tt('Status') }}:</label>
                    <select name="status" class="form-control">
                        <option value="pending">{{ tt('Pending') }}</option>
                        <option value="approved">{{ tt('Approved') }}</option>
                        <option value="rejected">{{ tt('Rejected') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Seller Modal -->
<div class="modal fade" id="editSellerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Edit Seller') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editSellerTabs">
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
                                <label>{{ tt('First Name') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]"
                                    class="form-control def_name">

                                <label class="mt-2">{{ tt('Last Name') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]"
                                    class="form-control def_last_name">
                            </div>
                        @endforeach
                    </div>

                    <!-- Other Fields -->
                    <label class="mt-2">{{ tt('Email') }}:</label>
                    <input type="email" name="email" class="form-control">

                    <label class="mt-2">{{ tt('Country Code') }}:</label>
                    <input type="text" name="country_code" class="form-control">

                    <label class="mt-2">{{ tt('Phone') }}:</label>
                    <input type="text" name="phone" class="form-control">

                    <label class="mt-2">{{ tt('Profile Picture') }}:</label>
                    <input type="file" name="profile_picture" class="form-control">

                    <label class="mt-2">{{ tt('Status') }}:</label>
                    <select name="status" class="form-control">
                        <option value="approved">{{ tt('Approved') }}</option>
                        <option value="pending">{{ tt('Pending') }}</option>
                        <option value="rejected">{{ tt('Rejected') }}</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Seller Modal -->
<div class="modal fade" id="previewSellerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ tt('Seller Details') }}</h5>
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
<!-- bulks -->
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this seller? This action cannot be undone.') }}")) {
            event.target.closest('form').submit();
        }
    }

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="seller_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('bulkDeleteForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="seller_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            alert('{{ tt('Select at least one seller to delete.') }}');
            return;
        }
        document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
        this.submit();
    });

    document.getElementById('bulkUpdateForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="seller_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            alert('{{ tt('Select at least one seller to update.') }}');
            return;
        }
        document.getElementById('bulkUpdateStatusIds').value = JSON.stringify(selectedIds);
        this.submit();
    });
</script>
<!-- nav -->
<script>
    document.querySelector('#sellers').classList.add('active');
    document.querySelector('#sellers .nav-link ').classList.add('active');
</script>
<!-- edit -->
<script>
    function editSeller(sellerId) {
        $.ajax({
            url: `/dashboard/sellers/${sellerId}`,
            type: 'GET',
            success: function (response) {
                let seller = response.seller;
                let translations = seller.translations;

                $('#editSellerModal form').attr('action', `/dashboard/sellers/${sellerId}`);
                $(".def_name").val(seller.first_name);
                $(".def_last_name").val(seller.last_name);
                // Populate Translations
                translations.forEach(translation => {
                    $(`#editSellerModal input[name="translations[${translation.language_id}][first_name]"]`).val(translation.first_name);
                    $(`#editSellerModal input[name="translations[${translation.language_id}][last_name]"]`).val(translation.last_name);
                });
                // Populate Other Fields
                $('#editSellerModal input[name="email"]').val(seller.email);
                $('#editSellerModal input[name="country_code"]').val(seller.country_code);
                $('#editSellerModal input[name="phone"]').val(seller.phone);
                $('#editSellerModal select[name="status"]').val(seller.artist_details.status);

                $('#editSellerModal').modal('show');
            },
            error: function () {
                alert('Failed to load seller details.');
            }
        });
    }
</script>
<!-- view -->
<script>
    function viewSeller(sellerId) {
        $.ajax({
            url: `/dashboard/sellers/${sellerId}`,
            type: 'GET',
            success: function (response) {
                let seller = response.seller;
                // Build HTML with seller details
                let detailsHtml = `
                <h5>${seller.first_name} ${seller.last_name}</h5>
                <p><strong>{{ tt('Email') }}:</strong> ${seller.email}</p>
                <p><strong>{{ tt('Phone') }}:</strong> ${seller.phone}</p>
                <p><strong>{{ tt('Registration Date') }}:</strong> ${seller.created_at.substring(0, 10)}</p>
                <p><strong>{{ tt('Status') }}:</strong> <span id="sellerStatus">${seller.artist_details.status}</span></p>
                <button onclick="toggleSellerStatus(${seller.id})" class="btn btn-secondary">{{ tt('Toggle Status') }}</button>
            `;
                $('#previewSellerModal .modal-body').html(detailsHtml);
                $('#previewSellerModal').modal('show');
            },
            error: function () {
                alert('Failed to load seller details.');
            }
        });
    }

    function toggleSellerStatus(sellerId) {
        $.ajax({
            url: `/dashboard/sellers/${sellerId}/toggle-status`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    alert('{{ tt('Seller status updated successfully.') }}');
                    viewSeller(sellerId); // Refresh preview
                }
            },
            error: function () {
                alert('{{ tt('Failed to update seller status. Please try again.') }}');
            }
        });
    }
</script>
@endsection