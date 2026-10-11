@if ($booking->is_walkin)
    <span class="tag tag-walkin"><i class="fas fa-walking" aria-hidden="true"></i> Walk-in</span>
@else
    <span class="tag tag-online"><i class="fas fa-globe" aria-hidden="true"></i> Online</span>
@endif
