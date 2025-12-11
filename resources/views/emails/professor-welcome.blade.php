<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to ASCC-IT</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Arial, sans-serif; color:#1f2937; }
    .container { max-width: 640px; margin: 0 auto; padding: 24px; background: #ffffff; }
    .header { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
    .subheader { font-size: 14px; color:#374151; margin-bottom: 16px; }
    .panel { background:#f9fafb; border:1px solid #e5e7eb; border-radius: 8px; padding:16px; margin:16px 0; }
    .label { font-weight:600; }
    .muted { color:#6b7280; font-size: 12px; }
    .btn { display:inline-block; padding:10px 16px; background:#14532d; color:#ffffff !important; text-decoration:none; border-radius:6px; font-weight:600; }
    .footer { margin-top: 24px; font-size: 12px; color:#6b7280; }
    code { background:#f3f4f6; color:#0f172a; padding:2px 6px; border-radius:4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">Welcome to ASCC-IT</div>
    <div class="subheader">Dear {{ $professorName }},</div>

    <p>We’re pleased to inform you that your faculty account has been created. Please find your temporary credentials below. For your security, kindly sign in and change your password at your earliest convenience.</p>

    <div class="panel">
      <div><span class="label">Email:</span> <code>{{ $email }}</code></div>
      <div><span class="label">Temporary password:</span> <code>{{ $tempPassword }}</code></div>
    </div>

    <p>You may access the portal using the button below:</p>
    <p>
      <a class="btn" href="{{ $loginUrl }}" target="_blank" rel="noopener" style="background:#14532d;color:#ffffff !important;text-decoration:none;display:inline-block;padding:10px 16px;border-radius:6px;font-weight:600;">Go to Professor Portal</a>
    </p>

    <p class="muted">Note: This temporary password is for one-time use only and may expire after first login or within a defined period. If you encounter any issues, please contact the administrator.</p>

    <div class="footer">
      Regards,<br/>
      ASCC IT Team
    </div>
  </div>
</body>
</html>
