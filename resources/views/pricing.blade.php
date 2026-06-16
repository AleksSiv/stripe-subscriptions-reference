<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a plan</title>
    <style>
        body{font-family:system-ui,sans-serif;max-width:760px;margin:48px auto;padding:0 20px;color:#1a1a1a}
        .flash{background:#fdf3e7;border:1px solid #e6c79c;padding:12px 16px;border-radius:6px;margin-bottom:24px}
        .tiers{display:flex;gap:20px;flex-wrap:wrap}
        .tier{flex:1;min-width:240px;border:1px solid #e3e3e3;border-radius:10px;padding:24px}
        .tier h2{margin:0 0 4px}.price{color:#1c5a87;font-weight:600;margin:0 0 12px}
        button{background:#1c5a87;color:#fff;border:0;border-radius:6px;padding:10px 18px;cursor:pointer;font-size:15px}
    </style>
</head>
<body>
    <h1>Choose your plan</h1>
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    <div class="tiers">
        @foreach ($tiers as $key => $tier)
            <div class="tier">
                <h2>{{ $tier['name'] }}</h2>
                <p class="price">{{ $tier['price'] }}</p>
                <p>{{ $tier['blurb'] }}</p>
                <form method="POST" action="{{ route('subscribe', $key) }}">
                    @csrf
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        @endforeach
    </div>
</body>
</html>
