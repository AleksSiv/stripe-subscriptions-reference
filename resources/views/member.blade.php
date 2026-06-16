<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Members area</title>
    <style>body{font-family:system-ui,sans-serif;max-width:680px;margin:48px auto;padding:0 20px}a{color:#1c5a87}</style>
</head>
<body>
    <h1>Members area</h1>
    <p>You are on the <strong>{{ ucfirst($tier ?? 'unknown') }}</strong> plan, and your listing is live.</p>
    <p>This page is gated by subscription state. If a renewal fails, Stripe moves the
       subscription to <code>past_due</code>, the webhook flips your listing to hidden,
       and this page stops being reachable, all without a manual step.</p>
    <p><a href="{{ route('account') }}">Manage billing</a></p>
</body>
</html>
