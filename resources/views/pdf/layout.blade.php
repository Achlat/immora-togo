<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>@yield('title')</title>
<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1c1c1f; line-height: 1.45; }
    .band { background: #0d0d0e; color: #ffffff; padding: 26px 40px 22px; }
    .band table { width: 100%; border-collapse: collapse; }
    .agency { font-size: 15pt; font-weight: bold; letter-spacing: 0.3px; }
    .agency-meta { color: #a9a59b; font-size: 7.5pt; margin-top: 4px; }
    .doc-type { text-align: right; color: #d8bd7e; font-size: 7.5pt; letter-spacing: 2px; text-transform: uppercase; }
    .doc-number { text-align: right; font-size: 12pt; font-weight: bold; margin-top: 3px; }
    .gold { height: 3px; background: #b8913e; }
    .content { padding: 26px 40px 90px; }
    .grid { width: 100%; border-collapse: collapse; }
    .grid td { vertical-align: top; }
    .label { color: #6f6c64; font-size: 7pt; letter-spacing: 1.4px; text-transform: uppercase; margin-bottom: 3px; }
    .strong { font-weight: bold; font-size: 10.5pt; }
    .muted { color: #6f6c64; }
    table.lines { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.lines th { text-align: left; font-size: 7pt; letter-spacing: 1.2px; text-transform: uppercase; color: #6f6c64;
        border-bottom: 1px solid #0d0d0e; padding: 7px 8px; }
    table.lines td { padding: 8px; border-bottom: 1px solid #e7e3d9; }
    table.lines .r { text-align: right; white-space: nowrap; }
    .total-box { margin-top: 18px; background: #0d0d0e; color: #fff; padding: 14px 18px; }
    .total-box table { width: 100%; border-collapse: collapse; }
    .total-label { color: #d8bd7e; font-size: 7.5pt; letter-spacing: 1.5px; text-transform: uppercase; }
    .total-amount { text-align: right; font-size: 17pt; font-weight: bold; }
    .words { margin-top: 8px; font-style: italic; color: #4a4945; }
    .stamp { border: 2px solid #b8913e; color: #85641c; display: inline-block; padding: 6px 14px; font-weight: bold;
        letter-spacing: 2px; text-transform: uppercase; font-size: 9pt; }
    .stamp.void { border-color: #9b2c2c; color: #9b2c2c; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 12px 40px 16px; border-top: 1px solid #e7e3d9;
        font-size: 7pt; color: #6f6c64; }
    .footer table { width: 100%; border-collapse: collapse; }
</style>
</head>
<body>
<div class="band">
    <table><tr>
        <td>
            <div class="agency">{{ $agency->name }}</div>
            <div class="agency-meta">
                {{ $agency->address }}@if ($agency->city), {{ $agency->city }}@endif
                @if ($agency->phone) · {{ \App\Support\Phone::display($agency->phone) }}@endif
                @if ($agency->email) · {{ $agency->email }}@endif
            </div>
        </td>
        <td style="width: 45%">
            <div class="doc-type">@yield('doc-type')</div>
            <div class="doc-number">@yield('doc-number')</div>
        </td>
    </tr></table>
</div>
<div class="gold"></div>

<div class="content">
    @yield('content')
</div>

<div class="footer">
    <table><tr>
        <td>{{ $agency->name }}@if ($agency->rccm) · RCCM {{ $agency->rccm }}@endif @if ($agency->tax_id) · NCC {{ $agency->tax_id }}@endif @if ($agency->license_number) · Agrément {{ $agency->license_number }}@endif</td>
        <td style="text-align: right">Document généré par Immora le {{ now()->format('d/m/Y à H:i') }}</td>
    </tr></table>
</div>
</body>
</html>
