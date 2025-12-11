<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{{ $title }}</title>
<style>body{font-family:Arial,sans-serif;background:#f5f7fa;margin:0;padding:40px;color:#12372a;} .card{max-width:520px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:28px;box-shadow:0 6px 18px rgba(0,0,0,.07);} h1{margin:0 0 12px;font-size:24px;} p{line-height:1.5;margin:0 0 16px;} a.btn{display:inline-block;background:#12372a;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;} a.btn:hover{background:#0d2a1f;} .note{font-size:12px;color:#555;margin-top:24px;} </style>
</head><body>
  <div class="card">
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <p><a href="{{ url('/') }}" class="btn">Go to Portal</a></p>
    <div class="note">If you believe this action is incorrect please contact support.</div>
  </div>
</body></html>
