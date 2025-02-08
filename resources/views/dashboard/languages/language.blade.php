@extends('dashboard.layouts.app')

@section('css')
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container bookings">
    <div class="title">
        <h3>{{ tt('Dictionary for') }} {{ tt($language->name) }}</h3>
        <a href="{{ route('dashboard.languages.index') }}" class="btn btn-secondary">{{ tt('Back to Languages') }}</a>
    </div>
    <hr>

    <!-- Search Bar -->
    <div class="d-flex justify-content-end separate">
        <form method="GET" action="{{ route('dashboard.languages.show', $language->id) }}" class="mb-3">
            <div class="input-group">
                @if (isset($_GET['search']))
                    <a href="{{ route('dashboard.languages.show', $language->id) }}"
                        class="btn btn-secondary me-0">{{ tt('Reset') }}</a>
                @endif
                <input type="text" name="search" class="form-control" aria-label="Search..."
                    placeholder="{{ tt('Search token or translation...') }}" value="{{ request('search', '') }}">
                <button type="submit" class="btn btn-primary">{{ tt('Search') }}</button>
            </div>
        </form>
    </div>

    <!-- Translations Table -->
    @if ($translations->isEmpty())
        <center class="alert alert-warning">{{ tt('No translations found.') }}</center>
    @else
        <form method="POST" action="{{ route('dashboard.languages.updateLanguage', $language->id) }}" id="translationForm">
            @csrf
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ tt('Token') }}</th>
                        <th>{{ tt('Translation') }} ({{ tt($language->name) }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($translations as $translation)
                        <tr>
                            <td>
                                <input type="hidden" name="translations[{{ $loop->index }}][id]"
                                    value="{{ $translation->id ?? '' }}">
                                <input type="text" name="translations[{{ $loop->index }}][token]" class="form-control"
                                    value="{{ $translation->token }}" readonly>
                            </td>
                            <td>
                                <input type="text" name="translations[{{ $loop->index }}][translation]" class="form-control"
                                    value="{{ $translation->translation }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($translations->hasPages())
                <nav aria-label="Page navigation example">
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if (!$translations->onFirstPage())
                            <a href="{{ $translations->previousPageUrl() }}" aria-label="Previous">
                                <li class="page-item arr">
                                    <i class="fas fa-chevron-left"></i>
                                </li>
                            </a>
                        @endif

                        @php
                            $total = $translations->lastPage();
                            $current = $translations->currentPage();
                            // Calculate start and end page numbers to display
                            $start = max($current - 2, 1);
                            $end = min($start + 4, $total);
                            // Adjust start if we are near the end to ensure we show 5 pages if possible
                            $start = max($end - 4, 1);
                        @endphp

                        @for ($i = $start; $i <= $end; $i++)
                            <a href="{{ $translations->url($i) }}">
                                <li class="page-item {{ $i == $current ? 'active' : '' }}">
                                    {{ $i }}
                                </li>
                            </a>
                        @endfor

                        {{-- Next Page Link --}}
                        @if ($translations->hasMorePages())
                            <a href="{{ $translations->nextPageUrl() }}" aria-label="Next">
                                <li class="page-item arr">
                                    <i class="fas fa-chevron-right"></i>
                                </li>
                            </a>
                        @endif
                    </ul>
                </nav>
            @endif

            <!-- Save Button -->
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">{{ tt('Save Translations') }}</button>
            </div>
        </form>
    @endif
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="toastMessage" class="toast align-items-center text-white bg-success border-0" role="alert"
        aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <span id="toastText"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.querySelector('#translationForm').addEventListener('submit', function (event) {
        event.preventDefault();

        let form = this;
        let formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, 'success'); // Show success toast
                setTimeout(() => hideToast(), 1500); // Hide after short delay
            })
            .catch(error => {
                showToast('{{ tt('Error updating translations.') }}', 'danger'); // Show error toast
            });
    });

    function showToast(message, type = 'success') {
        let toastEl = document.getElementById('toastMessage');
        let toastText = document.getElementById('toastText');
        toastEl.classList.remove('bg-success', 'bg-danger');
        toastEl.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
        toastText.innerText = message;

        let toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    function hideToast() {
        let toastEl = document.getElementById('toastMessage');
        let toast = new bootstrap.Toast(toastEl);
        toast.hide();
    }
</script>

<script>
    document.querySelector('#languages').classList.add('active');
    document.querySelector('#languages .nav-link ').classList.add('active');
</script>
@endsection