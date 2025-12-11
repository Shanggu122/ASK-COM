<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Today's Consultation Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
    <tr>
      <td style="background:#12372a;color:#fff;padding:16px 20px;font-size:18px;font-weight:bold;">Consultation Reminder</td>
    </tr>
    <tr>
      <td style="padding:20px;color:#12372a;line-height:1.55;">
        <p style="margin:0 0 12px;font-weight:600;">Professor {{ $professorName ?? '' }}</p>
        <p style="margin:0 0 16px;">You have a consultation scheduled <strong>today ({{ $bookingDate }})</strong> with <strong>{{ $studentName }}</strong> – <strong>{{ $subjectName }}</strong> ({{ $typeName }}).</p>
        <p style="margin:0 0 14px;">Please confirm how you wish to proceed:</p>
        <p style="margin:0 0 22px;">
          <a href="{{ URL::signedRoute('consultation.email.accept',[ 'bookingId'=>$bookingId,'profId'=>$profId ]) }}" style="background:#0f9657;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;font-weight:600;display:inline-block;margin-right:10px;">Accept</a>
          <a href="{{ URL::signedRoute('consultation.email.reschedule.form',[ 'bookingId'=>$bookingId,'profId'=>$profId ]) }}" style="background:#d49100;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;font-weight:600;display:inline-block;">Reschedule</a>
        </p>
        <p style="margin:0 0 10px;font-size:12px;color:#555;">Signed links remain valid unless the booking details change.</p>
        <p style="margin:28px 0 8px;font-size:12px;color:#777;">If already handled in the system, no further action is required.</p>
  <p style="margin-top:34px;">Regards,<br>ASCC-IT</p>
      </td>
    </tr>
  </table>
</body>
</html>
