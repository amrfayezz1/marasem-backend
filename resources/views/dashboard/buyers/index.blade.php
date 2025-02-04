@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Buyers List</h3>
        <button data-bs-toggle="modal" data-bs-target="#addBuyerModal" class="btn btn-primary">
            Add Buyer &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.buyers.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['date_joined']))
                    <a href="{{ route('dashboard.buyers.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="Search by Name or ID"
                    value="{{ request('search', '') }}">

                <input type="text" name="date_range" id="dateRangePicker" class="form-control"
                    placeholder="Select Date Range" value="{{ request('date_range', '') }}">

                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Buyers Table -->
    @if ($buyers->isEmpty())
        <center class="alert alert-warning">No buyers found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buyers as $buyer)
                    <tr>
                        <td><input type="checkbox" name="buyer_ids[]" value="{{ $buyer->id }}"></td>
                        <td>{{ $buyer->id }}</td>
                        <td>{{ $buyer->first_name }} {{ $buyer->last_name }}</td>
                        <td>{{ $buyer->email }}</td>
                        <td>{{ $buyer->country_code }}{{ $buyer->phone }}</td>
                        <td>{{ $buyer->created_at->format('Y-m-d') }}</td>
                        <td>
                            <span onclick="editBuyer({{ $buyer->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.buyers.destroy', $buyer->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $buyers->links() }}

        <!-- Bulk Actions -->
        <div class="bulks mt-3">
            <form method="POST" action="{{ route('dashboard.buyers.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-danger">Delete Selected</button>
            </form>

            <!-- <form method="POST" action="{{ route('dashboard.buyers.bulk-update-profile') }}" class="d-inline">
                                                                @csrf
                                                                <div class="input-group">
                                                                    <select name="profile_type" class="form-select w-auto">
                                                                        <option value="regular">Regular</option>
                                                                        <option value="vip">VIP</option>
                                                                    </select>
                                                                    <input type="hidden" name="ids" id="bulkUpdateProfileIds">
                                                                    <button type="submit" class="btn btn-primary">Update Profile Type</button>
                                                                </div>
                                                            </form> -->
        </div>
    @endif
</div>

<!-- Add Buyer Modal -->
<div class="modal fade" id="addBuyerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('dashboard.buyers.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Buyer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="buyerTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label>First Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]" class="form-control"
                                    required>

                                <label class="mt-2">Last Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]" class="form-control"
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

                    <label class="mt-2">Date Joined:</label>
                    <input type="date" name="date_joined" class="form-control" required>

                    <hr>
                    <h5>Address Details</h5>

                    <label class="mt-2">Address Name:</label>
                    <input type="text" name="address[name]" class="form-control" placeholder="Home, Work, etc."
                        required>

                    <label class="mt-2">City:</label>
                    <input type="text" name="address[city]" class="form-control" required>

                    <label class="mt-2">Zone:</label>
                    <input type="text" name="address[zone]" class="form-control" required>

                    <label class="mt-2">Address:</label>
                    <textarea name="address[address]" class="form-control" required></textarea>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="address[is_default]" value="1"
                            id="defaultAddressCheck">
                        <label class="form-check-label" for="defaultAddressCheck">
                            Set as default address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Buyer Modal -->
<div class="modal fade" id="editBuyerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Buyer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editBuyerTabs">
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
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
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

                    <label class="mt-2">Date Joined:</label>
                    <input type="date" name="date_joined" class="form-control">
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
<!-- date -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function () {
        $('#dateRangePicker').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear'
            }
        });

        $('#dateRangePicker').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#dateRangePicker').on('cancel.daterangepicker', function () {
            $(this).val('');
        });
    });
</script>
<!-- bulk -->
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this buyer? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="buyer_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('bulkDeleteForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const selectedIds = Array.from(document.querySelectorAll('input[name="buyer_ids[]"]:checked'))
            .map(cb => cb.value);
        if (selectedIds.length === 0) {
            alert('Select at least one buyer to delete.');
            return;
        }
        document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
        this.submit();
    });
</script>
<!-- edit -->
<script>
    function editBuyer(buyerId) {
        $.ajax({
            url: `/dashboard/buyers/${buyerId}`,
            type: 'GET',
            success: function (response) {
                let buyer = response.buyer;
                let translations = buyer.translations;
                let address = response.address;

                $('#editBuyerModal form').attr('action', `/dashboard/buyers/${buyerId}`);

                // Populate Translations
                translations.forEach(translation => {
                    $(`#editBuyerModal input[name="translations[${translation.language_id}][first_name]"]`).val(translation.first_name);
                    $(`#editBuyerModal input[name="translations[${translation.language_id}][last_name]"]`).val(translation.last_name);
                });

                // Populate Other Fields
                $('#editBuyerModal input[name="email"]').val(buyer.email);
                $('#editBuyerModal input[name="phone"]').val(buyer.phone);
                $('#editBuyerModal input[name="country_code"]').val(buyer.country_code);
                $('#editBuyerModal input[name="date_joined"]').val(buyer.created_at.split("T")[0]);

                $('#editBuyerModal').modal('show');
            },
            error: function () {
                alert('Failed to load buyer details.');
            }
        });
    }
</script>

<!-- nav -->
<script>
    document.querySelector('#buyers').classList.add('active');
    document.querySelector('#buyers .nav-link ').classList.add('active');
</script>
@endsection