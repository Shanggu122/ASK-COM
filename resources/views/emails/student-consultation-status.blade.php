<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Consultation Status</title></head>
<body style="font-family:Arial,sans-serif;background:#f5f7fa;padding:20px;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#12372a;color:#fff;padding:16px 20px;font-size:18px;font-weight:600;">Consultation Status Update</td></tr>
    <tr><td style="padding:20px;color:#12372a;line-height:1.5;">
      <p style="margin-top:0;">Hello {{ $studentName }},</p>
      @if($status==='accepted')
        <p>Your consultation with <strong>{{ $professorName }}</strong> has been <strong>ACCEPTED</strong> for <strong>{{ $date }}</strong>.</p>
      @elseif($status==='rescheduled')
        <p>Your consultation with <strong>{{ $professorName }}</strong> has been <strong>RESCHEDULED</strong> to <strong>{{ $date }}</strong>.</p>
        @if($reason)
          <p><em>Reason:</em> {{ $reason }}</p>
        @endif
      @endif
      <p>Please log in to the portal for details.</p>
  <p style="margin-top:32px;">Regards,<br>ASCC-IT</p>
    </td></tr>
  </table>
</body></html>
