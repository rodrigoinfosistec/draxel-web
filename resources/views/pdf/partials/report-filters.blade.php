@if (!empty($items))
    <div class="filters">
        @foreach ($items as $label => $value)
            <div class="filters-row">
                <strong>{{ $label }}:</strong> {{ filled($value) ? $value : '—' }}
            </div>
        @endforeach
    </div>
@endif
