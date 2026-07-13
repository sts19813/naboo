<div class="row g-5 mb-8">
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-4 py-6">
                <div class="symbol symbol-40px">
                    <span class="symbol-label bg-light-warning">
                        <i class="ki-outline ki-time text-warning fs-3"></i>
                    </span>
                </div>
                <div>
                    <div class="text-muted fs-7">Total pendientes</div>
                    <div class="fw-bold fs-2">${{ number_format((float) ($summary['pending_total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-4 py-6">
                <div class="symbol symbol-40px">
                    <span class="symbol-label bg-light-success">
                        <i class="ki-outline ki-check text-success fs-3"></i>
                    </span>
                </div>
                <div>
                    <div class="text-muted fs-7">Total pagados</div>
                    <div class="fw-bold fs-2">${{ number_format((float) ($summary['paid_total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-4 py-6">
                <div class="symbol symbol-40px">
                    <span class="symbol-label bg-light-danger">
                        <i class="ki-outline ki-information-3 text-danger fs-3"></i>
                    </span>
                </div>
                <div>
                    <div class="text-muted fs-7">Total atrasados</div>
                    <div class="fw-bold fs-2">${{ number_format((float) ($summary['overdue_total'] ?? 0), 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
