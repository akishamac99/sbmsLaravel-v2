@extends('layouts.portal')
@section('title', 'Book Services')

@push('head')
    @vite(['resources/css/customer-services.css', 'resources/js/customer-services.js'])
@endpush

@section('content')
<div id="services-page" class="owner-stack"
     data-availability-url="{{ route('customer.staff-availability') }}"
     data-update-url="{{ route('customer.cart.update', ['cart' => '__ID__']) }}"
     data-remove-url="{{ route('customer.cart.remove', ['cart' => '__ID__']) }}"
     data-downpayment-percent="{{ $downpaymentPercent }}"
     data-home-fee="{{ number_format($homeFee) }}">

    <div class="page-header">
        <div class="page-title">
            <h1>Book a Service</h1>
            <p>Pick a service, choose a time, then check out from your cart.</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-ghost" data-scroll-to="#bookingCart"><i class="fas fa-shopping-cart"></i> Cart ({{ $cartItems->count() }})</button>
        </div>
    </div>

    @if ($hasUnpaid)
        <div class="warning-msg" style="margin:0">
            <i class="fas fa-exclamation-triangle"></i>
            <span>You have a booking waiting for payment. <a href="{{ route('customer.bookings') }}" style="text-decoration:underline">Settle it</a> before checking out new services.</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-inline" role="alert" style="margin:0">{{ $errors->first() }}</div>
    @endif

    <div class="dashboard-grid">
        {{-- ============================ Catalog ============================ --}}
        <section class="card">
            <div class="card-header">
                <span>Service Catalog</span>
                <span class="pill p-conf">{{ $eventCategoryCount }} event categories</span>
            </div>
            <div class="card-body">
                <div class="service-filter-panel">
                    <div class="owner-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="serviceSearch" class="form-control" placeholder="Search services...">
                    </div>
                    <div class="service-filter-chips">
                        <button type="button" class="service-filter-chip active" data-category="all">All</button>
                        @foreach ($categories as $category)
                            <button type="button" class="service-filter-chip" data-category="{{ $category->catID }}">{{ $category->category_name }}</button>
                        @endforeach
                    </div>
                </div>

                <div id="serviceEmptyState" class="owner-empty" style="display:none;">No services match your filters.</div>

                @foreach ($categories as $category)
                    <div class="category-section service-category-group" data-category="{{ $category->catID }}">
                        <h2 class="category-title">
                            <i class="fas {{ $category->icon }}"></i> {{ $category->category_name }}
                            @if ($category->is_event_type)
                                <span class="event-badge">Event Service</span>
                            @endif
                        </h2>
                        <div class="services-grid">
                            @foreach ($category->services as $service)
                                <div class="service-card service-catalog-card"
                                     data-category="{{ $category->catID }}"
                                     data-name="{{ strtolower($service->serviceName . ' ' . $service->description . ' ' . $category->category_name) }}"
                                     data-home="{{ (int) $service->is_home_service }}">
                                    <button type="button" class="service-card-action" data-open-booking
                                            data-service-id="{{ $service->serviceID }}"
                                            data-name="{{ $service->serviceName }}"
                                            data-price="{{ number_format($service->servicePrice) }}"
                                            data-duration="{{ $service->duration_label }}"
                                            data-home="{{ (int) $service->is_home_service }}"
                                            aria-label="Book {{ $service->serviceName }}"><i class="fas fa-plus"></i></button>
                                    <div class="service-img" style="background-image: url('{{ $service->image_url }}');"></div>
                                    <div class="service-info">
                                        <h3>{{ $service->serviceName }}</h3>
                                        <p>{{ \Illuminate\Support\Str::limit($service->description, 80) }}</p>
                                        <div class="service-card-footer">
                                            <span class="service-price">PHP {{ number_format($service->servicePrice) }}</span>
                                            <span class="service-duration"><i class="far fa-clock"></i> {{ $service->duration_label }}</span>
                                        </div>
                                        @if ($service->is_home_service)
                                            <span class="home-service-badge"><i class="fas fa-home"></i> Home Service Available</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ============================ Cart ============================ --}}
        <aside>
            <div class="card sticky-booking-cart" id="bookingCart">
                <div class="card-header">
                    <span>Booking Cart</span>
                    <span class="badge" id="cartSelectedTotal">&#8369;{{ number_format($cartItems->whereIn('cartID', $selectedIds)->sum(fn ($i) => (float) $i->service->servicePrice)) }}</span>
                </div>
                <div class="card-body">
                    @if ($cartItems->isNotEmpty())
                        <form method="POST" action="{{ route('customer.cart.checkout') }}" id="checkoutSelectionForm">
                            @csrf
                            <div class="cart-list">
                                @foreach ($cartItems as $item)
                                    <div class="cart-item">
                                        <input type="checkbox" name="selected_cart[]" value="{{ $item->cartID }}"
                                               class="checkout-item-check" data-price="{{ (float) $item->service->servicePrice }}"
                                               aria-label="Select {{ $item->service->serviceName }}"
                                               @checked(in_array((int) $item->cartID, $selectedIds, true))>
                                        <div>
                                            <span class="cell-main">{{ $item->service->serviceName }}</span>
                                            <span class="cell-sub">
                                                &#8369;{{ number_format($item->service->servicePrice) }} &middot;
                                                @if ($item->bookingDate)
                                                    {{ \Carbon\Carbon::parse($item->bookingDate)->format('M d') }}, {{ \Carbon\Carbon::parse($item->bookingTime)->format('g:i A') }}
                                                @else
                                                    <span style="color:var(--warning)">needs a schedule</span>
                                                @endif
                                            </span>
                                            @if ($homeEligible[$item->cartID] ?? false)
                                                <select name="service_mode[{{ $item->cartID }}]" class="form-control" aria-label="Where">
                                                    <option value="salon" @selected(($modes[$item->cartID] ?? 'salon') === 'salon')>In salon</option>
                                                    <option value="home_service" @selected(($modes[$item->cartID] ?? '') === 'home_service')>Home service</option>
                                                </select>
                                            @endif
                                        </div>
                                        <div class="cart-item-actions">
                                            <button type="button" class="icon-btn" data-edit-cart data-cart-id="{{ $item->cartID }}" aria-label="Edit {{ $item->service->serviceName }}"><i class="fas fa-pen"></i></button>
                                            <button type="button" class="icon-btn danger" data-delete-cart data-cart-id="{{ $item->cartID }}" data-name="{{ $item->service->serviceName }}" aria-label="Remove {{ $item->service->serviceName }}"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="cart-footer">
                                <div class="walkin-total">
                                    <span>Selected <span class="cell-sub">Downpayment ({{ number_format($downpaymentPercent) }}%): &#8369;<span id="selectedDownpayment">{{ number_format($cartItems->whereIn('cartID', $selectedIds)->sum(fn ($i) => (float) $i->service->servicePrice) * $downpaymentPercent / 100, 2) }}</span></span></span>
                                </div>
                                @if (! $hasUnpaid)
                                    <button type="submit" class="btn-primary w-full">Checkout selected</button>
                                @else
                                    <button type="button" class="btn-secondary w-full" disabled>Settle your unpaid booking first</button>
                                @endif
                            </div>
                        </form>
                    @else
                        <p class="empty-state" style="padding:20px 0 !important"><i class="fas fa-shopping-cart"></i>Your cart is empty. Tap <strong>+</strong> on a service to add it.</p>
                    @endif
                </div>
            </div>

        </aside>
    </div>

    <script type="application/json" id="cart-data">@json($cartData)</script>

    {{-- ============================ Add to cart modal ============================ --}}
    <div class="modal" id="bookingModal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Book Service</h3>
                    <button type="button" class="close-modal" data-close-modal>&times;</button>
                </div>
                <form method="POST" action="{{ route('customer.cart.add') }}" data-staff-form>
                    @csrf
                    <input type="hidden" name="service_id">
                    <div class="form-group">
                        <label>Service</label>
                        <input type="text" id="serviceName" class="form-control" disabled>
                    </div>
                    <div class="form-group customer-warning-inline" id="homeServiceWarning">
                        <i class="fas fa-info-circle"></i> <span id="homeServiceMsg"></span>
                    </div>
                    <div class="form-group">
                        <label>Select Date</label>
                        <input type="date" name="booking_date" class="form-control beauty-date" min="{{ $minDate }}" max="{{ $maxDate }}" data-placeholder="Select booking date" data-schedule-field required>
                    </div>
                    <div class="form-group">
                        <label>Select Time</label>
                        @include('customer.partials.time-picker', ['id' => 'timePickerWrapper', 'groups' => $timeSlots])
                    </div>
                    <div class="form-group">
                        <label>Select Staff</label>
                        <select name="staff_id" class="form-control" required>
                            <option value="">Choose date and time first</option>
                        </select>
                        <small data-staff-hint>Available staff will appear after selecting a date and time.</small>
                    </div>
                    <div class="form-group">
                        <label>Special Requests</label>
                        <textarea name="special_request" class="form-control" rows="2" placeholder="Allergies, preferred style, custom service details, or other requests"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                    <div class="customer-modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================ Edit cart item modal ============================ --}}
    <div class="modal" id="editCartModal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Cart Item</h3>
                    <button type="button" class="close-modal" data-close-modal>&times;</button>
                </div>
                <form method="POST" action="#" data-staff-form>
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>Service</label>
                        <input type="text" id="editServiceName" class="form-control" disabled>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Date</label>
                            <input type="date" name="booking_date" class="form-control beauty-date" min="{{ $minDate }}" max="{{ $maxDate }}" data-placeholder="Select booking date" data-schedule-field required>
                        </div>
                        <div class="form-group">
                            <label>Select Time</label>
                            @include('customer.partials.time-picker', ['id' => 'editTimePickerWrapper', 'groups' => $timeSlots])
                        </div>
                        <div class="form-group full">
                            <label>Select Staff</label>
                            <select name="staff_id" class="form-control" required>
                                <option value="">Choose date and time first</option>
                            </select>
                            <small data-staff-hint>Only available staff for the selected service and slot will appear.</small>
                        </div>
                        <div class="form-group full">
                            <label>Special Requests</label>
                            <textarea name="special_request" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================ Remove from cart modal ============================ --}}
    <div class="modal" id="deleteCartModal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Delete Service</h3>
                    <button type="button" class="close-modal" data-close-modal>&times;</button>
                </div>
                <form method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="warning-msg customer-no-margin">
                        <i class="fas fa-exclamation-triangle"></i>
                        Remove <strong id="deleteServiceName"></strong> from your cart?
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
