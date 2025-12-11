<!DOCTYPE html>
<html>
<head>
    <title>Notification Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Notification Debug Information</h1>
    
    <h2>Recent Notifications ({{ $notifications->count() }})</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Professor</th>
            <th>Booking ID</th>
            <th>Type</th>
            <th>Title</th>
            <th>Message</th>
            <th>Is Read</th>
            <th>Created At</th>
        </tr>
        @foreach($notifications as $notification)
        <tr>
            <td>{{ $notification->id }}</td>
            <td>{{ $notification->professor_name }}</td>
            <td>{{ $notification->booking_id }}</td>
            <td>{{ $notification->type }}</td>
            <td>{{ $notification->title }}</td>
            <td>{{ $notification->message }}</td>
            <td>{{ $notification->is_read ? 'Yes' : 'No' }}</td>
            <td>{{ $notification->created_at }}</td>
        </tr>
        @endforeach
    </table>
    
    <h2>Recent Bookings ({{ $bookings->count() }})</h2>
    <table>
        <tr>
            <th>Booking ID</th>
            <th>Student</th>
            <th>Professor</th>
            <th>Date</th>
            <th>Status</th>
            <th>Created At</th>
        </tr>
        @foreach($bookings as $booking)
        <tr>
            <td>{{ $booking->Booking_ID }}</td>
            <td>{{ $booking->student_name }}</td>
            <td>{{ $booking->professor_name }}</td>
            <td>{{ $booking->Booking_Date }}</td>
            <td>{{ $booking->Status }}</td>
            <td>{{ $booking->Created_At }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
