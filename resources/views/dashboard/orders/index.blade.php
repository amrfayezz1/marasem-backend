@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Orders') }}</h3>
    </div>
    <hr>

    <!-- Search & Filter Bar -->
    <div class="d-flex justify-content-end separate">
        <form method="GET" action="{{ route('dashboard.orders.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']) || isset($_GET['status']) || isset($_GET['date_range']))
                    <a href="{{ route('dashboard.orders.index') }}" class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="{{ tt('Search by ID or Client Name') }}"
                    value="{{ request('search', '') }}">

                <select name="status" class="form-select">
                    <option value="all">{{ tt('All Statuses') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ tt('Pending') }}</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ tt('Completed') }}</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ tt('Cancelled') }}</option>
                </select>

                <input type="text" name="date_range" id="dateRangePicker" class="form-control"
                    placeholder="{{ tt('Select Date Range') }}" value="{{ request('date_range', '') }}">

                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    @if ($orders->isEmpty())
        <center class="alert alert-warning">{{ tt('No orders found.') }}</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="select"><input type="checkbox" id="selectAll"> {{ tt('Select') }}</th>
                    <th>{{ tt('Order ID') }}</th>
                    <th>{{ tt('Customer') }}</th>
                    <th>{{ tt('Mobile') }}</th>
                    <th>{{ tt('Address') }}</th>
                    <th>{{ tt('Date & Time') }}</th>
                    <th>{{ tt('Status') }}</th>
                    <th>{{ tt('Total Amount') }}</th>
                    <th>{{ tt('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"></td>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->user->first_name }} {{ $order->user->last_name }}</td>
                        <td>{{ $order->user->phone }}</td>
                        <td>{{ $order->address->address }}</td>
                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <span
                                class="badge {{ $order->order_status == 'pending' ? 'bg-warning' : ($order->order_status == 'completed' ? 'bg-success' : ($order->order_status == 'deleted' ? 'bg-danger' : 'bg-secondary')) }}">
                                {{ ucfirst($order->order_status) }}
                            </span>
                        </td>
                        <td>${{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span onclick="viewOrder({{ $order->id }})"><i class="fa-solid fa-eye"></i></span>
                            <span onclick="editOrder({{ $order->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <form action="{{ route('dashboard.orders.destroy', $order->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmDelete(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        
        @if ($orders->hasPages())
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    @if (!$orders->onFirstPage())
                        <a href="{{ $orders->previousPageUrl() }}" aria-label="Previous">
                            <li class="page-item arr">
                                <i class="fas fa-chevron-left"></i>
                            </li>
                        </a>
                    @endif
                    @for ($i = 1; $i <= $orders->lastPage(); $i++)
                        <a href="{{ $orders->url($i) }}">
                            <li class="page-item {{ $i == $orders->currentPage() ? 'active' : '' }}">
                                {{ $i }}
                            </li>
                        </a>
                    @endfor
                    @if ($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}" aria-label="Next">
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
            <form method="POST" action="{{ route('dashboard.orders.bulk-delete') }}" id="bulkDeleteForm">
                @csrf
                <input type="hidden" name="ids" id="bulkDeleteIds">
                <button type="submit" class="btn btn-danger">{{ tt('Delete Selected') }}</button>
            </form>

            <form method="POST" action="{{ route('dashboard.orders.bulk-update-status') }}" id="bulkUpdateStatusForm"
                class="d-inline">
                @csrf
                <div class="input-group">
                    <select name="status" class="form-select w-auto">
                        <option value="pending">{{ tt('Pending') }}</option>
                        <option value="completed">{{ tt('Completed') }}</option>
                        <option value="cancelled">{{ tt('Cancelled') }}</option>
                    </select>
                    <input type="hidden" name="ids" id="bulkUpdateStatusIds">
                    <button type="submit" class="btn btn-primary">{{ tt('Update Status') }}</button>
                </div>
            </form>
        </div>
    @endif
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ tt('Order Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ tt('Edit Order') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>{{ tt('Order Status') }}:</label>
                    <select name="order_status" class="form-control">
                        <option value="pending">{{ tt('Pending') }}</option>
                        <option value="completed">{{ tt('Completed') }}</option>
                        <option value="cancelled">{{ tt('Cancelled') }}</option>
                    </select>

                    <label class="mt-2">{{ tt('Customer') }}:</label>
                    <input type="text" name="customer_name" class="form-control" readonly>

                    <label class="mt-2">{{ tt('Customer Phone') }}:</label>
                    <input type="text" name="customer_phone" class="form-control" readonly>

                    <label class="mt-2">{{ tt('Address') }}:</label>
                    <input type="text" name="address" class="form-control" readonly>
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
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- date -->
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
<!-- view -->
<script>
    function viewOrder(orderId) {
        $.ajax({
            url: `/dashboard/orders/${orderId}`,
            type: 'GET',
            success: function (response) {
                let order = response.order;
                console.log(order);
                let artworksHtml = order.items.map(item => `
                    <div class="d-flex align-items-center border-bottom py-2">
                        <img src="${item.artwork.photos ? JSON.parse(item.artwork.photos)[0] : ''}" class="rounded" width="50" height="50" style="margin-right: 10px;">
                        <div>
                            <strong>${item.artwork.name}</strong>
                            <p class="text-muted">{{ tt('Size') }}: ${item.size}, {{ tt('Quantity') }}: ${item.quantity}, {{ tt('Price') }}: $${parseFloat(item.price).toFixed(2)}</p>
                        </div>
                    </div>
                `).join('');

                $('#viewOrderModal .modal-body').html(`
                    <h5>{{ tt('Order ID') }}: ${order.id}</h5>
                    <p><strong>{{ tt('Customer') }}:</strong> ${order.user.first_name} ${order.user.last_name}</p>
                    <p><strong>{{ tt('Mobile') }}:</strong> ${order.user.phone}</p>
                    <p><strong>{{ tt('Address') }}:</strong> ${order.address.address}, ${order.address.city}, ${order.address.zone}</p>
                    <p><strong>{{ tt('Total') }}:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                    <p><strong>{{ tt('Status') }}:</strong> ${order.order_status}</p>
                    <h6>{{ tt('Ordered Artworks') }}:</h6>
                    ${artworksHtml}
                `);
                $('#viewOrderModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load order details.') }}');
            }
        });
    }
</script>
<!-- edit -->
<script>
    function editOrder(orderId) {
        $.ajax({
            url: `/dashboard/orders/${orderId}`,
            type: 'GET',
            success: function (response) {
                let order = response.order;

                $('#editOrderModal select[name="order_status"]').val(order.order_status);
                $('#editOrderModal input[name="customer_name"]').val(order.user.first_name + ' ' + order.user.last_name);
                $('#editOrderModal input[name="customer_phone"]').val(order.user.phone);
                $('#editOrderModal input[name="address"]').val(order.address.address + ', ' + order.address.zone + ', ' + order.address.city);

                $('#editOrderModal form').attr('action', `/dashboard/orders/${order.id}`);
                $('#editOrderModal').modal('show');
            },
            error: function () {
                alert('{{ tt('Failed to load order details.') }}');
            }
        });
    }
</script>
<!-- bulks -->
<script>
    function confirmDelete(event) {
        event.preventDefault();
        if (confirm('{{ tt('Are you sure you want to delete this order?') }}')) {
            event.target.closest('form').submit();
        }
    }

    // Select/Deselect All Checkboxes
    document.querySelector('#selectAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="order_ids[]"]').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Handle Bulk Delete Submission
    document.querySelector('#bulkDeleteForm').addEventListener('submit', function (event) {
        event.preventDefault();
        let selectedIds = Array.from(document.querySelectorAll('input[name="order_ids[]"]:checked'))
            .map(checkbox => checkbox.value);

        if (selectedIds.length === 0) {
            alert('{{ tt('Select at least one order to delete.') }}');
            return;
        }

        document.querySelector('#bulkDeleteIds').value = JSON.stringify(selectedIds);
        this.submit();
    });

    // Handle Bulk Update Status Submission
    document.querySelector('#bulkUpdateStatusForm').addEventListener('submit', function (event) {
        event.preventDefault();
        let selectedIds = Array.from(document.querySelectorAll('input[name="order_ids[]"]:checked'))
            .map(checkbox => checkbox.value);

        if (selectedIds.length === 0) {
            alert('{{ tt('Select at least one order to update.') }}');
            return;
        }

        document.querySelector('#bulkUpdateStatusIds').value = JSON.stringify(selectedIds);
        this.submit();
    });
</script>
<!-- sidebar -->
<script>
    document.querySelector('#orders').classList.add('active');
    document.querySelector('#orders .nav-link ').classList.add('active');
</script>
@endsection
