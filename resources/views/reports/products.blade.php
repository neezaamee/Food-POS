@extends('layouts.app')

@section('title', 'Product Sales & Best Sellers')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Product Performance & Best Sellers</h4>
            <p class="text-muted mb-0 small">Item sales frequency, gross revenues, and menu popularity ranking</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Product Rankings</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 60px;">Rank</th>
                        <th>Product Name</th>
                        <th class="text-center">Total Quantity Sold</th>
                        <th class="text-end pe-3">Gross Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productStats as $idx => $item)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">
                            @if($idx === 0)
                                <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill"></i> #1</span>
                            @elseif($idx === 1)
                                <span class="badge bg-secondary-subtle text-secondary">#2</span>
                            @elseif($idx === 2)
                                <span class="badge bg-danger-subtle text-danger">#3</span>
                            @else
                                #{{ $productStats->firstItem() + $idx }}
                            @endif
                        </td>
                        <td class="fw-bold text-body">{{ $item->product_name }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-1">
                                {{ number_format($item->total_qty) }}
                            </span>
                        </td>
                        <td class="text-end pe-3 fw-bold text-success fs-6">
                            Rs. {{ number_format($item->gross_sales, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No sales data recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($productStats->hasPages())
    <div class="card-footer bg-transparent border-0 px-3 py-2">
        {{ $productStats->links() }}
    </div>
    @endif
</div>
@endsection
