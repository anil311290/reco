@php
    $line = $line ?? null;
@endphp
<tr class="line-row">
    <td>
        <select class="form-select form-select-sm item-select w-100" data-searchable="true" data-placeholder="Search item">
            <option value="">Select Item</option>
            @foreach($items as $item)
            <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}" {{ $line && $line->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
            @endforeach
        </select>
        <input type="text" class="form-control form-control-sm mt-1 bg-light description-input" value="{{ $line->description ?? '' }}" placeholder="Description" readonly>
    </td>
    <td><input type="number" class="form-control form-control-sm qty-input" value="{{ $line->quantity ?? 1 }}" min="0.001" step="0.001"></td>
    <td><input type="number" class="form-control form-control-sm price-input" value="{{ $line->unit_price ?? 0 }}" min="0" step="0.01"></td>
    <td><input type="number" class="form-control form-control-sm disc-input" value="{{ $line->discount_percentage ?? 0 }}" min="0" max="100" step="0.01"></td>
    <td>
        <select class="form-select form-select-sm tax-select w-100">
            <option value="">No Tax</option>
            @foreach($taxRates as $tax)
            <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate ?? $tax->rate }}" {{ $line && $line->tax_rate_id == $tax->id ? 'selected' : '' }}>{{ $tax->tax_name ?? $tax->name }} ({{ $tax->tax_rate ?? $tax->rate }}%)</option>
            @endforeach
        </select>
    </td>
    <td><input type="text" class="form-control form-control-sm line-total" value="{{ $line ? '₹' . number_format($line->total, 2) : '' }}" readonly></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
</tr>
