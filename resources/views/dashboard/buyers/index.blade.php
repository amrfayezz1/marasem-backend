@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Buyers List') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addBuyerModal" class="btn btn-primary">
            {{ tt('Add Buyer') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.buyers.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['date_joined']))
                    <a href="{{ route('dashboard.buyers.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="{{ tt('Search by Name or ID') }}"
                    value="{{ request('search', '') }}">

                <input type="text" name="date_range" id="dateRangePicker" class="form-control"
                    placeholder="{{ tt('Select Date Range') }}" value="{{ request('date_range', '') }}">

                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Buyers Table -->
    @if ($buyers->isEmpty())
        <center class="alert alert-warning">{{ tt('No buyers found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('ID') }}</th>
                    <th>{{ tt('Name') }}</th>
                    <th>{{ tt('Email') }}</th>
                    <th>{{ tt('Phone') }}</th>
                    <th>{{ tt('Date Joined') }}</th>
                    <th>{{ tt('Actions') }}</th>
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
                            <span onclick="viewBuyer({{ $buyer->id }})"><i class="fa-solid fa-eye"></i></span>
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

        
        @if ($buyers->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$buyers->onFirstPage())
                        <a href="{{ $buyers->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $buyers->lastPage(); $i++)
                        <a href="{{ $buyers->url($i) }}">
                            <li class="page-item {{ $i == $buyers->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($buyers->hasMorePages())
                        <a href="{{ $buyers->nextPageUrl() }}" aria-label="Next">
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
            <form method="POST" action="{{ route('dashboard.buyers.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-danger">{{ tt('Delete Selected') }}</button>
            </form>

            <!-- <form method="POST" action="{{ route('dashboard.buyers.bulk-update-profile') }}" class="d-inline">
                                                                            @csrf
                                                                            <div class="input-group">
                                                                                <select name="profile_type" class="form-select w-auto">
                                                                                    <option value="regular">{{ tt('Regular') }}</option>
                                                                                    <option value="vip">{{ tt('VIP') }}</option>
                                                                                </select>
                                                                                <input type="hidden" name="ids" id="bulkUpdateProfileIds">
                                                                                <button type="submit" class="btn btn-primary">{{ tt('Update Profile Type') }}</button>
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
                    <h5 class="modal-title">{{ tt('Add Buyer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="buyerTabs">
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
                                <input type="text" name="translations[{{ $language->id }}][first_name]" class="form-control"
                                    required>

                                <label class="mt-2">{{ tt('Last Name') }}:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]" class="form-control"
                                    required>
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

                    <label class="mt-2">{{ tt('Date Joined') }}:</label>
                    <input type="date" name="date_joined" class="form-control" required>

                    <hr>
                    <h5>{{ tt('Address Details') }}</h5>

                    <label class="mt-2">{{ tt('Address Name') }}:</label>
                    <input type="text" name="address[name]" class="form-control" placeholder="{{ tt('Home, Work, etc.') }}"
                        required>

                    <label class="mt-2">{{ tt('City') }}:</label>
                    <input type="text" name="address[city]" class="form-control" required>

                    <label class="mt-2">{{ tt('Zone') }}:</label>
                    <input type="text" name="address[zone]" class="form-control" required>

                    <label class="mt-2">{{ tt('Address') }}:</label>
                    <textarea name="address[address]" class="form-control" required></textarea>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="address[is_default]" value="1"
                            id="defaultAddressCheck">
                        <label class="form-check-label" for="defaultAddressCheck">
                            {{ tt('Set as default address') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
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
                    <h5 class="modal-title">{{ tt('Edit Buyer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editBuyerTabs">
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

                    <label class="mt-2">{{ tt('Date Joined') }}:</label>
                    <input type="date" name="date_joined" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Buyer Modal -->
<div class="modal fade" id="previewBuyerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ tt('Buyer Details') }}</h5>
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
                cancelLabel: '{{ tt('Clear') }}'
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
        if (confirm("{{ tt('Are you sure you want to delete this buyer? This action cannot be undone.') }}")) {
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
            alert('{{ tt('Select at least one buyer to delete.') }}');
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
                alert('{{ tt('Failed to load buyer details.') }}');
            }
        });
    }
</script>
<!-- view -->
<script>
    function viewBuyer(buyerId) {
        $.ajax({
            url: `/dashboard/buyers/${buyerId}`,
            type: 'GET',
            success: function (response) {
                let buyer = response.buyer;
                // Assume buyer.address is available (or load via relationship)
                let address = buyer.address;
                let previewHtml = `

                <h5>${buyer.first_name} ${buyer.last_name}</h5>
                <p><strong>{{ tt('Email') }}:</strong> ${buyer.email}</p>
                <p><strong>{{ tt('Phone') }}:</strong> ${buyer.country_code}${buyer.phone}</p>
                <p><strong>{{ tt('Date Joined') }}:</strong> ${buyer.created_at.substring(0, 10)}</p>
                ${address ? `<p><strong>{{ tt('Address') }}:</strong> ${address.address}, ${address.city}, ${address.zone}</p>` : ''}
            `;
                $('#previewBuyerModal .modal-body').html(previewHtml);
                $('#previewBuyerModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Unable to load buyers. Please try again later.') }}');
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
