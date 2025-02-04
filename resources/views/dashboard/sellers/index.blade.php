@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Seller List</h3>
        <button data-bs-toggle="modal" data-bs-target="#addSellerModal" class="btn btn-primary">
            Add Seller &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.sellers.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['status']))
                    <a href="{{ route('dashboard.sellers.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="Search by Name or ID"
                    value="{{ request('search', '') }}">

                <select name="status" class="form-select">
                    <option value="all">All Statuses</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Seller Table -->
    @if ($sellers->isEmpty())
        <center class="alert alert-warning">No sellers found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Profile Picture</th>
                    <th>Registration Date</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                                <span>N/A</span>
                            @endif
                        </td>
                        <td>{{ $seller->created_at->format('Y-m-d') }}</td>
                        <td>
                            <span
                                class="badge {{ $seller->artistDetails->status == 'approved' ? 'bg-success' : ($seller->artistDetails->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                {{ ucfirst($seller->artistDetails->status) }}
                            </span>
                        </td>
                        <td>
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

        {{ $sellers->links() }}

        <!-- Bulk Actions -->
        <div class="bulks mt-3">
            <form method="POST" action="{{ route('dashboard.sellers.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-danger">Delete Selected</button>
            </form>

            <form method="POST" action="{{ route('dashboard.sellers.bulk-update-status') }}" class="d-inline"
                id="bulkUpdateForm">
                @csrf
                <div class="input-group">
                    <select name="status" class="form-select w-auto">
                        <option value="approved">Approve</option>
                        <option value="pending">Mark as Pending</option>
                        <option value="rejected">Reject</option>
                    </select>
                    <input type="hidden" name="ids" id="bulkUpdateStatusIds">
                    <button type="submit" class="btn btn-primary">Update Status</button>
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
                    <h5 class="modal-title">Add Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="sellerTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]" value="{{ $language->id }}">
                                <label>First Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]" class="form-control lang-fname"
                                    required>

                                <label class="mt-2">Last Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]" class="form-control lang-lname"
                                    required>
                            </div>
                        @endforeach
                    </div>

                    <!-- Other Fields -->
                    <label class="mt-2">Email:</label>
                    <input type="email" name="email" class="form-control" required>

                    <label class="mt-2">Country Code:</label>
                    <input type="text" name="country_code" class="form-control" value="+20" required>

                    <label class="mt-2">Phone:</label>
                    <input type="text" name="phone" class="form-control" required>

                    <label class="mt-2">Profile Picture:</label>
                    <input type="file" name="profile_picture" class="form-control">

                    <label class="mt-2">Status:</label>
                    <select name="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
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
                    <h5 class="modal-title">Edit Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editSellerTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]" value="{{ $language->id }}">
                                <label>First Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]"
                                    class="form-control def_name">

                                <label class="mt-2">Last Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]"
                                    class="form-control def_last_name">
                            </div>
                        @endforeach
                    </div>

                    <!-- Other Fields -->
                    <label class="mt-2">Email:</label>
                    <input type="email" name="email" class="form-control">

                    <label class="mt-2">Country Code:</label>
                    <input type="text" name="country_code" class="form-control">

                    <label class="mt-2">Phone:</label>
                    <input type="text" name="phone" class="form-control">

                    <label class="mt-2">Profile Picture:</label>
                    <input type="file" name="profile_picture" class="form-control">

                    <label class="mt-2">Status:</label>
                    <select name="status" class="form-control">
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<!-- bulks -->
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this seller? This action cannot be undone.")) {
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
            alert('Select at least one seller to delete.');
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
            alert('Select at least one seller to update.');
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
@endsection