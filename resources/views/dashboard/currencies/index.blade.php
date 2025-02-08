@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Currencies') }}</h3>
        <button data-bs-toggle="modal" data-bs-target="#addCurrencyModal" class="btn btn-primary">
            {{ tt('Create') }} &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.currencies.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']))
                    <a href="{{ route('dashboard.currencies.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..."
                    placeholder="{{ tt('Search by Name, Symbol or Rate...') }}" value="{{ request('search', '') }}">
                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Currencies Table -->
    @if ($currencies->isEmpty())
        <center class="alert alert-warning">{{ tt('No currencies found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('Name') }}</th>
                    <th>{{ tt('Symbol') }}</th>
                    <th>{{ tt('Rate') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($currencies as $currency)
                    <tr>
                        <td><input type="checkbox" name="currency_ids[]" value="{{ $currency->id }}"></td>
                        <td>{{ $currency->name }}</td>
                        <td>{{ $currency->symbol }}</td>
                        <td>{{ $currency->rate }}</td>
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


        @if ($currencies->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$currencies->onFirstPage())
                        <a href="{{ $currencies->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $currencies->lastPage(); $i++)
                        <a href="{{ $currencies->url($i) }}">
                            <li class="page-item {{ $i == $currencies->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($currencies->hasMorePages())
                        <a href="{{ $currencies->nextPageUrl() }}" aria-label="Next">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-right"></i>
                            </li>
                        </a>
                    @endif
                </ul>
            </nav>
        @endif

        <!-- Bulk Delete Button -->
        <form method="POST" action="{{ route('dashboard.currencies.bulk-delete') }}" id="bulkDeleteForm">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger mt-3">{{ tt('Delete Selected') }}</button>
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
                    <h5 class="modal-title">{{ tt('Add Currency') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>{{ tt('Currency Name:') }}</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">

                    <label class="mt-2">{{ tt('Symbol:') }}</label>
                    <input type="text" name="symbol" class="form-control" required maxlength="10">

                    <label class="mt-2">{{ tt('Rate:') }}</label>
                    <input type="text" name="rate" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save') }}</button>
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
                    <h5 class="modal-title">{{ tt('Edit Currency') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>{{ tt('Currency Name:') }}</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">

                    <label class="mt-2">{{ tt('Symbol:') }}</label>
                    <input type="text" name="symbol" class="form-control" required maxlength="10">

                    <label class="mt-2">{{ tt('Rate:') }}</label>
                    <input type="text" name="rate" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ tt('Save Changes') }}</button>
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
                alert('{{ tt('Failed to load currency details.') }}');
            }
        });
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm("{{ tt('Are you sure you want to delete this currency? This action cannot be undone.') }}")) {
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
            alert('{{ tt('Select at least one currency.') }}');
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