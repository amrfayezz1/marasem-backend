@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Currencies</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCurrencyModal" class="btn btn-primary">
            Create &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.currencies.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']))
                    <a href="{{ route('dashboard.currencies.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..."
                    placeholder="Search by Name or Symbol..." value="{{ request('search', '') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Currencies Table -->
    @if ($currencies->isEmpty())
        <center class="alert alert-warning">No currencies found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> Select</th>
                    <th>Name</th>
                    <th>Symbol</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($currencies as $currency)
                    <tr>
                        <td><input type="checkbox" name="currency_ids[]" value="{{ $currency->id }}"></td>
                        <td>{{ $currency->name }}</td>
                        <td>{{ $currency->symbol }}</td>
                        <td>
                            <span onclick="editCurrency({{ $currency->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.currencies.destroy', $currency->id) }}" method="POST"
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

        {{ $currencies->links() }}

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.currencies.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">Delete Selected</button>
        </form>
    @endif
</div>

<!-- Add Currency Modal -->
<div class="modal fade" id="addCurrencyModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.currencies.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Currency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Currency Name:</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">

                    <label class="mt-2">Symbol:</label>
                    <input type="text" name="symbol" class="form-control" required maxlength="10">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Currency Modal -->
<div class="modal fade" id="editCurrencyModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Currency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Currency Name:</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">

                    <label class="mt-2">Symbol:</label>
                    <input type="text" name="symbol" class="form-control" required maxlength="10">
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
<script>
    function editCurrency(currencyId) {
        $.ajax({
            url: `/dashboard/currencies/${currencyId}`,
            type: 'GET',
            success: function (response) {
                let currency = response.currency;
                $('#editCurrencyModal input[name="name"]').val(currency.name);
                $('#editCurrencyModal input[name="symbol"]').val(currency.symbol);
                $('#editCurrencyModal form').attr('action', `/dashboard/currencies/${currency.id}`);
                $('#editCurrencyModal').modal('show');
            },
            error: function () {
                alert('Failed to load currency details.');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this currency? This action cannot be undone.")) {
            event.target.closest('form').submit();
        }
    }

    function getSelectedCurrencyIds() {
        return Array.from(document.querySelectorAll('input[name="currency_ids[]"]:checked'))
            .map(cb => cb.value);
    }

    function updateBulkDeleteInput() {
        const selectedIds = getSelectedCurrencyIds();
        if (selectedIds.length === 0) {
            alert('Select at least one currency.');
            return false;
        }
        document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
        return true;
    }

    document.getElementById('bulkDeleteForm').addEventListener('submit', function (event) {
        if (!updateBulkDeleteInput()) {
            event.preventDefault();
        }
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="currency_ids[]"]').forEach(cb => cb.checked = this.checked);
    });

</script>
<script>
    document.querySelector('#currencies').classList.add('active');
    document.querySelector('#currencies .nav-link ').classList.add('active');
</script>
@endsection