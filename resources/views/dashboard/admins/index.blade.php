@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>Admins List</h3>
        <button data-bs-toggle="modal" data-bs-target="#addAdminModal" class="btn btn-primary">
            Add Admin &nbsp; <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <hr>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end seperate">
        <form method="GET" action="{{ route('dashboard.admins.index') }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']))
                    <a href="{{ route('dashboard.admins.index') }}" class="btn btn-secondary me-0">Reset</a>
                @endif
                <input type="text" name="search" class="form-control" placeholder="Search by Name or Email"
                    value="{{ request('search', '') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <!-- Admins Table -->
    @if ($admins->isEmpty())
        <center class="alert alert-warning">No admins found.</center>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                    <tr>
                        <td>{{ $admin->id }}</td>
                        <td>{{ $admin->first_name }} {{ $admin->last_name }}</td>
                        <td>{{ $admin->email }}</td>
                        <td>
                            <span onclick="editAdmin({{ $admin->id }})"><i class="fa-solid fa-pen-to-square"></i></span>
                            <span onclick="editPrivileges({{ $admin->id }})"><i class="fa-solid fa-cogs"></i></span>
                            <form action="{{ route('dashboard.admins.remove', $admin->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <span onclick="confirmRemove(event)"><i class="fa-solid fa-trash"></i></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $admins->links() }}
    @endif
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dashboard.admins.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Select Existing User:</label>
                    <select name="user_id" class="form-control select2">
                        <option value="">-- Select User --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}
                                ({{ $user->email }})</option>
                        @endforeach
                    </select>

                    <hr>
                    <h5>Or Create New Admin</h5>

                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="adminTabs">
                        @foreach($languages as $language)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                    href="#admin-lang-{{ $language->id }}">
                                    {{ $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="admin-lang-{{ $language->id }}">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label class="mt-2">First Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]"
                                    class="form-control">

                                <label class="mt-2">Last Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]" class="form-control">
                            </div>
                        @endforeach
                    </div>

                    <label class="mt-2">Email:</label>
                    <input type="email" name="email" class="form-control">

                    <label class="mt-2">Password:</label>
                    <input type="password" name="password" class="form-control">

                    <label class="mt-2">Country Code:</label>
                    <input type="text" name="country_code" class="form-control">

                    <label class="mt-2">Phone:</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs" id="editAdminTabs">
                        @foreach($languages as $language)
                            <li class="nav-item">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                    href="#edit-admin-lang-{{ $language->id }}">
                                    {{ $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Language Tab Content -->
                    <div class="tab-content mt-3">
                        @foreach($languages as $language)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="edit-admin-lang-{{ $language->id }}">
                                <input type="hidden" name="translations[{{ $language->id }}][language_id]"
                                    value="{{ $language->id }}">
                                <label class="mt-2">First Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][first_name]"
                                    class="form-control def_name">

                                <label class="mt-2">Last Name:</label>
                                <input type="text" name="translations[{{ $language->id }}][last_name]"
                                    class="form-control def_last_name">
                            </div>
                        @endforeach
                    </div>
                    <label>Email:</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Privileges Modal -->
<div class="modal fade" id="editPrivilegesModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin Privileges</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="privileges-grid">
                        @foreach($privileges as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input privilege-checkbox" type="checkbox" name="privileges[]"
                                    value="{{ $key }}" id="privilege_{{ $key }}">
                                <label class="form-check-label" for="privilege_{{ $key }}">
                                    {{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>
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
    function confirmRemove(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to remove this admin?")) {
            event.target.closest('form').submit();
        }
    }
</script>
<!-- edit admin -->
<script>
    function editAdmin(adminId) {
        $.ajax({
            url: `/dashboard/admins/${adminId}`,
            type: 'GET',
            success: function (response) {
                let admin = response;
                let translations = admin.translations;

                $('#editAdminModal input[name="email"]').val(admin.email);
                $('#editAdminModal form').attr('action', `/dashboard/admins/${adminId}`);

                // Populate Translations
                translations.forEach(translation => {
                    $(`#editAdminModal input[name="translations[${translation.language_id}][first_name]"]`).val(translation.first_name);
                    $(`#editAdminModal input[name="translations[${translation.language_id}][last_name]"]`).val(translation.last_name);
                });

                $('#editAdminModal').modal('show');
            },
            error: function () {
                alert('Failed to load admin details.');
            }
        });
    }
</script>
<!-- edit previlige -->
<script>
    function editPrivileges(adminId) {
        $.ajax({
            url: `/dashboard/admins/${adminId}`,
            type: 'GET',
            success: function (response) {
                let admin = response;
                console.log(admin)
                let privileges = JSON.parse(admin.admin_privileges.privileges || '[]');

                $('.privilege-checkbox').prop('checked', false);
                privileges.forEach(priv => {
                    $(`#privilege_${priv}`).prop('checked', true);
                });

                $('#editPrivilegesModal form').attr('action', `/dashboard/admins/${adminId}/update-privileges`);
                $('#editPrivilegesModal').modal('show');
            },
            error: function () {
                alert('Failed to load admin privileges.');
            }
        });
    }
</script>

<!-- nav -->
<script>
    document.querySelector('#admins').classList.add('active');
    document.querySelector('#admins .nav-link ').classList.add('active');
</script>
@endsection