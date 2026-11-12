<h1>Janet's Angel Beauty Salon - Operational Report</h1>
<p>Generated {{ $generatedAt }}. Filters: {{ $filterSummary }}</p>

<h2>Summary</h2>
<table><caption style="text-align:left">Booking and revenue totals</caption>
    <tbody>
        <tr><th scope="row">Total bookings</th><td>{{ $totalBookings }}</td></tr>
        <tr><th scope="row">Pending bookings</th><td>{{ $pendingBookings }}</td></tr>
        <tr><th scope="row">Completed bookings</th><td>{{ $completedBookings }}</td></tr>
        <tr><th scope="row">Completed revenue</th><td>₱{{ number_format($completedRevenue, 2) }}</td></tr>
        <tr><th scope="row">Feedback received</th><td>{{ $feedbackCount }}</td></tr>
    </tbody>
</table>

<h2>Revenue by service</h2>
<table><thead><tr><th scope="col">Service</th><th scope="col">Category</th><th scope="col">Bookings</th><th scope="col">Revenue</th></tr></thead><tbody>
    @forelse ($byService as $row)<tr><td>{{ $row->serviceName }}</td><td>{{ $row->category ?? 'Uncategorized' }}</td><td>{{ $row->bookings_count }}</td><td>₱{{ number_format((float) $row->total_revenue, 2) }}</td></tr>
    @empty<tr><td colspan="4">No completed activity yet.</td></tr>@endforelse
</tbody></table>

<h2>Staff performance</h2>
<table><thead><tr><th scope="col">Staff member</th><th scope="col">Completed bookings</th><th scope="col">Feedback</th><th scope="col">Average rating</th></tr></thead><tbody>
    @forelse ($staffPerformance as $row)<tr><td>{{ $row->staff_name }}</td><td>{{ $row->completed }}</td><td>{{ $row->feedback_count }}</td><td>{{ $row->avg_rating ? number_format((float) $row->avg_rating, 1) : '-' }}</td></tr>
    @empty<tr><td colspan="4">No staff performance data yet.</td></tr>@endforelse
</tbody></table>
