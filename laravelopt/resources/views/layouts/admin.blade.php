<!DOCTYPE html>
<html lang="ru" data-admin-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OPTECH — Admin')</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232563eb'/%3E%3Cpath d='M16 4l3.5 8.5L28 16l-8.5 3.5L16 28l-3.5-8.5L4 16l8.5-3.5z' fill='white'/%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-ru-RU.min.js"></script>

    <script>
        (function(){var t=localStorage.getItem('admin-theme');if(t==='dark')document.documentElement.setAttribute('data-admin-theme','dark')})();
    </script>

    <style>
        :root, [data-admin-theme="dark"] {
            --admin-bg:#06111f; --admin-panel:#0a1729; --admin-panel-2:#0f2037;
            --admin-border:rgba(130,156,196,.14); --admin-text:#f4f8ff; --admin-muted:#93a4c2;
            --admin-blue:#3b82f6; --admin-cyan:#38bdf8; --admin-green:#22c55e; --admin-orange:#f59e0b; --admin-red:#ef4444;
            --admin-input-bg:rgba(255,255,255,.045); --admin-input-border:rgba(130,156,196,.15); --admin-input-text:#fff;
            --admin-sidebar-bg:rgba(6,17,31,.82); --admin-topbar-bg:rgba(6,17,31,.76);
            --admin-card-bg:linear-gradient(180deg,rgba(11,23,41,.96),rgba(8,18,33,.96));
            --admin-card-shadow:inset 0 1px 0 rgba(255,255,255,.035);
            --admin-grid-color:rgba(255,255,255,.026);
            --sidebar-width:272px; --topbar-height:86px; --radius-lg:24px;
        }
        [data-admin-theme="light"] {
            --admin-bg:#f0f4f8; --admin-panel:#fff; --admin-panel-2:#f7f9fc;
            --admin-border:rgba(0,0,0,.08); --admin-text:#0f1729; --admin-muted:#5f6d85;
            --admin-input-bg:#f4f7fb; --admin-input-border:rgba(0,0,0,.10); --admin-input-text:#0f1729;
            --admin-sidebar-bg:rgba(255,255,255,.92); --admin-topbar-bg:rgba(255,255,255,.88);
            --admin-card-bg:linear-gradient(180deg,#fff,#fafcff); --admin-card-shadow:0 1px 3px rgba(0,0,0,.04);
            --admin-grid-color:rgba(0,0,0,.03);
        }
        [data-admin-theme="light"] body{background:linear-gradient(135deg,#eef2f8 0%,#f5f8fc 42%,#e9eef5 100%);color:var(--admin-text);}
        [data-admin-theme="light"] body::before{background-image:linear-gradient(var(--admin-grid-color) 1px,transparent 1px),linear-gradient(90deg,var(--admin-grid-color) 1px,transparent 1px);}
        [data-admin-theme="light"] .ad-sidebar{background:var(--admin-sidebar-bg);}
        [data-admin-theme="light"] .ad-brand-title,[data-admin-theme="light"] .ad-page-title,[data-admin-theme="light"] .ad-card h3,[data-admin-theme="light"] .ad-section-head h3{color:#0f1729;}
        [data-admin-theme="light"] .ad-nav-link{color:#4a5568;}
        [data-admin-theme="light"] .ad-nav-link:hover{color:#0f1729;background:rgba(0,0,0,.04);}
        [data-admin-theme="light"] .ad-nav-link.is-active{background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(56,189,248,.06));border-color:rgba(59,130,246,.18);color:#1e40af;}
        [data-admin-theme="light"] .ad-nav-icon{background:rgba(0,0,0,.05);color:#4a6fa5;}
        [data-admin-theme="light"] .ad-nav-link.is-active .ad-nav-icon{background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;}
        [data-admin-theme="light"] .ad-topbar{background:var(--admin-topbar-bg);}
        [data-admin-theme="light"] .ad-panel-shell,[data-admin-theme="light"] .ad-card,[data-admin-theme="light"] .ad-stat-card,[data-admin-theme="light"] .ad-form-section,[data-admin-theme="light"] .ad-side-card{background:var(--admin-card-bg);border-color:var(--admin-border);box-shadow:var(--admin-card-shadow);}
        [data-admin-theme="light"] .ad-field span,[data-admin-theme="light"] .ad-check-field span{color:#374151;}
        [data-admin-theme="light"] .ad-field input,[data-admin-theme="light"] .ad-field textarea,[data-admin-theme="light"] .ad-field select{background:var(--admin-input-bg);border-color:var(--admin-input-border);color:var(--admin-input-text);}
        [data-admin-theme="light"] .ad-btn{border-color:var(--admin-border);background:rgba(0,0,0,.03);color:#0f1729;}
        [data-admin-theme="light"] .ad-btn-primary{background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;}
        [data-admin-theme="light"] .ad-btn-primary:hover{background:linear-gradient(135deg,#1d4ed8,#0ea5e9);color:#fff;}
        [data-admin-theme="light"] table th{color:#374151;} [data-admin-theme="light"] table td{color:#0f1729;} [data-admin-theme="light"] table tr{border-color:var(--admin-border);}

        .ad-theme-toggle{width:46px;height:46px;border-radius:14px;border:1px solid var(--admin-border);background:rgba(255,255,255,.045);color:var(--admin-muted);cursor:pointer;display:grid;place-items:center;font-size:1.1rem;transition:.22s;position:relative;overflow:hidden;}
        .ad-theme-toggle:hover{border-color:rgba(59,130,246,.35);color:#fbbf24;}
        .ad-theme-toggle .fa-sun,.ad-theme-toggle .fa-moon{position:absolute;transition:transform .4s cubic-bezier(.68,-.55,.27,1.55),opacity .3s;}
        [data-admin-theme="dark"] .ad-theme-toggle .fa-sun{transform:translateY(0);opacity:1;} [data-admin-theme="dark"] .ad-theme-toggle .fa-moon{transform:translateY(24px);opacity:0;}
        [data-admin-theme="light"] .ad-theme-toggle .fa-sun{transform:translateY(-24px);opacity:0;} [data-admin-theme="light"] .ad-theme-toggle .fa-moon{transform:translateY(0);opacity:1;color:#6366f1;}

        *{box-sizing:border-box;} html,body{min-height:100%;}
        body{margin:0;font-family:'Inter',system-ui,-apple-system,sans-serif;color:var(--admin-text);background:radial-gradient(circle at 16% 12%,rgba(59,130,246,.16),transparent 32%),radial-gradient(circle at 92% 8%,rgba(56,189,248,.12),transparent 30%),linear-gradient(135deg,#06111f 0%,#081528 42%,#050b15 100%);}
        body::before{content:'';position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.026) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.026) 1px,transparent 1px);background-size:42px 42px;mask-image:linear-gradient(to bottom,rgba(0,0,0,.8),transparent 75%);}
        a{color:inherit;text-decoration:none;} a:hover,a:focus{text-decoration:none;} button{font-family:inherit;}
        .ad-admin-shell{min-height:100vh;padding-left:var(--sidebar-width);}
        .ad-sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar-width);padding:22px 18px;background:var(--admin-sidebar-bg);border-right:1px solid var(--admin-border);backdrop-filter:blur(18px);z-index:20;display:flex;flex-direction:column;overflow-y:auto;}
        .ad-brand{display:flex;align-items:center;gap:12px;min-height:58px;padding:0 10px 20px;border-bottom:1px solid var(--admin-border);margin-bottom:14px;}
        .ad-brand-mark{width:44px;height:44px;border-radius:16px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#2563eb,#38bdf8);box-shadow:0 14px 34px rgba(37,99,235,.34);}
        .ad-brand-title{margin:0;font-size:1.16rem;font-weight:800;letter-spacing:-.04em;line-height:1.05;}
        .ad-brand-title span{color:var(--admin-blue);}
        .ad-brand-subtitle{margin-top:4px;font-size:.78rem;color:var(--admin-muted);}
        .ad-brand--logo{flex-direction:column;align-items:center;gap:10px;padding:0 6px 0;margin:0 -18px 14px;}
        .ad-brand-logo{width:100%;max-width:100%;height:auto;color:var(--admin-text);display:block;margin:0 auto;}
        .ad-brand--logo .ad-brand-subtitle{width:100%;text-align:center;margin-top:0;}
        [data-admin-theme="dark"] .ad-brand-logo{color:#fff;}
        .ad-nav-label{padding:0 12px;margin:14px 0 8px;color:#6f83a5;text-transform:uppercase;font-size:.66rem;letter-spacing:.16em;font-weight:800;}
        .ad-nav{display:grid;gap:5px;}
        .ad-nav-link{min-height:44px;border-radius:14px;padding:0 12px;display:flex;align-items:center;gap:12px;color:#c8d5ef;border:1px solid transparent;transition:.2s;font-size:.92rem;}
        .ad-nav-link:hover{color:#fff;background:rgba(255,255,255,.045);border-color:rgba(130,156,196,.10);}
        .ad-nav-link.is-active{background:linear-gradient(135deg,rgba(59,130,246,.22),rgba(56,189,248,.10));border-color:rgba(59,130,246,.28);color:#fff;}
        .ad-nav-icon{width:32px;height:32px;border-radius:11px;display:grid;place-items:center;background:rgba(255,255,255,.045);color:#8ab4ff;flex:0 0 32px;font-size:.85rem;}
        .ad-nav-link.is-active .ad-nav-icon{background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;}
        .ad-sidebar-footer{margin-top:auto;border-radius:18px;border:1px solid rgba(59,130,246,.18);background:linear-gradient(180deg,rgba(59,130,246,.10),rgba(255,255,255,.025));padding:14px;color:var(--admin-muted);font-size:.82rem;line-height:1.5;}
        .ad-sidebar-footer strong{display:block;color:#fff;margin-bottom:5px;}
        .ad-main{position:relative;min-height:100vh;}
        .ad-topbar{height:var(--topbar-height);position:sticky;top:0;z-index:10;padding:0 28px;display:flex;align-items:center;justify-content:space-between;gap:20px;background:var(--admin-topbar-bg);border-bottom:1px solid rgba(130,156,196,.10);backdrop-filter:blur(18px);}
        .ad-breadcrumbs{display:flex;align-items:center;margin-bottom:6px;font-size:.82rem;}
        .ad-breadcrumbs a{color:#6f83a5;transition:.2s;display:inline-flex;align-items:center;} .ad-breadcrumbs a:hover{color:#8ab4ff;}
        .ad-breadcrumbs__sep{color:#3d5278;font-size:.6rem;margin:0 8px;} .ad-breadcrumbs__current{color:#c8d5ef;font-weight:700;}
        .ad-page-title{margin:0;font-size:1.35rem;line-height:1.1;font-weight:800;letter-spacing:-.04em;}
        .ad-profile{display:flex;align-items:center;gap:12px;border-radius:18px;border:1px solid var(--admin-border);background:rgba(255,255,255,.035);padding:9px 10px 9px 12px;}
        .ad-profile-avatar{width:42px;height:42px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(135deg,#2563eb,#38bdf8);font-weight:800;color:#fff;}
        .ad-profile-name{font-weight:700;line-height:1.1;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .ad-profile-role{color:var(--admin-muted);font-size:.78rem;margin-top:4px;}
        .ad-logout-btn{width:42px;height:42px;border-radius:14px;border:1px solid rgba(239,68,68,.20);background:rgba(239,68,68,.08);color:#ffb4b4;cursor:pointer;transition:.2s;}
        .ad-logout-btn:hover{background:rgba(239,68,68,.16);color:#fff;}
        .ad-content{padding:28px;}
        .ad-panel-shell,.ad-card,.ad-stat-card,.ad-form-section,.ad-side-card{border:1px solid rgba(130,156,196,.11);background:linear-gradient(180deg,rgba(11,23,41,.96),rgba(8,18,33,.96));border-radius:var(--radius-lg);box-shadow:inset 0 1px 0 rgba(255,255,255,.035);}
        .ad-panel-shell{padding:24px;}

        /* Buttons */
        .ad-btn{min-height:46px;padding:0 16px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;gap:10px;border:1px solid rgba(130,156,196,.14);background:rgba(255,255,255,.045);color:#edf4ff;font-weight:800;cursor:pointer;transition:.2s;font-size:.9rem;}
        .ad-btn:hover{transform:translateY(-1px);border-color:rgba(59,130,246,.35);background:rgba(59,130,246,.12);}
        .ad-btn-primary{border-color:rgba(59,130,246,.38);background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;box-shadow:0 16px 34px rgba(37,99,235,.22);}
        .ad-btn-primary:hover{background:linear-gradient(135deg,#1d4ed8,#0ea5e9);border-color:rgba(59,130,246,.6);color:#fff;box-shadow:0 18px 40px rgba(37,99,235,.34);}
        .ad-btn-danger{border-color:rgba(239,68,68,.26);background:rgba(239,68,68,.10);color:#fecaca;}
        .ad-btn-sm{min-height:38px;padding:0 12px;font-size:.82rem;border-radius:12px;}

        /* Page header */
        .ad-page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;}
        .ad-page-head h2{margin:0;font-size:1.5rem;font-weight:800;letter-spacing:-.04em;}
        .ad-page-head p{margin:6px 0 0;color:var(--admin-muted);font-size:.9rem;}

        /* Form layout */
        .ad-form-actions-top{display:flex;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
        .ad-form-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.5fr);gap:18px;align-items:start;}
        .ad-form-main{display:grid;gap:18px;min-width:0;}
        .ad-form-side{display:grid;gap:18px;position:sticky;top:calc(var(--topbar-height) + 18px);}
        .ad-form-section{padding:22px;}
        .ad-section-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:18px;}
        .ad-section-head.mb-0{margin-bottom:0;}
        .ad-section-icon{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#2563eb,#38bdf8);flex:0 0 42px;}
        .ad-section-head h3{margin:0;font-size:1.12rem;letter-spacing:-.02em;} .ad-section-head p{margin:4px 0 0;color:var(--admin-muted);font-size:.85rem;line-height:1.5;}
        .ad-fields-grid{display:grid;gap:14px;} .ad-fields-grid.two{grid-template-columns:repeat(2,minmax(0,1fr));} .ad-fields-grid.three{grid-template-columns:repeat(3,minmax(0,1fr));}
        .ad-form-grid{display:grid;grid-template-columns:1fr;gap:16px;max-width:900px;}
        .ad-file{display:inline-flex;align-items:center;gap:10px;padding:0 18px;height:46px;border-radius:12px;border:1px solid var(--admin-border);background:rgba(59,130,246,.06);color:var(--admin-text);font-weight:700;cursor:pointer;transition:.18s;font-size:.9rem;max-width:320px;}
        .ad-file:hover{border-color:rgba(59,130,246,.55);background:rgba(59,130,246,.12);transform:translateY(-1px);}
        .ad-file i{color:#4eb0fb;}
        .ad-file input[type="file"]{display:none;}
        .ad-file span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .ad-field{display:flex;flex-direction:column;gap:7px;min-width:0;} .ad-field.span-2{grid-column:1/-1;}
        .ad-field>span{font-size:.84rem;font-weight:700;color:#c8d5ef;}
        .ad-field small{color:var(--admin-muted);font-size:.76rem;}
        .ad-field input,.ad-field textarea,.ad-field select{width:100%;min-height:46px;border-radius:13px;border:1px solid var(--admin-input-border);background:var(--admin-input-bg);color:var(--admin-input-text);padding:11px 14px;font-size:.92rem;font-family:inherit;transition:.18s;}
        .ad-field textarea{min-height:auto;resize:vertical;line-height:1.55;}
        .ad-field input:focus,.ad-field textarea:focus,.ad-field select:focus{outline:none;border-color:rgba(59,130,246,.55);background:rgba(59,130,246,.06);}
        select option,select optgroup{background:#ffffff;color:#0f1729;}
        [data-admin-theme="dark"] select option,[data-admin-theme="dark"] select optgroup{background:#101a2e;color:#e6edf7;}
        [data-admin-theme="light"] select option,[data-admin-theme="light"] select optgroup{background:#ffffff;color:#0f1729;}
        /* Кастомный светло-синий скроллбар */
        *{scrollbar-width:thin;scrollbar-color:#4eb0fb transparent;}
        ::-webkit-scrollbar{width:11px;height:11px;}
        ::-webkit-scrollbar-track{background:transparent;}
        ::-webkit-scrollbar-thumb{background:#4eb0fb;border-radius:10px;border:3px solid transparent;background-clip:content-box;}
        ::-webkit-scrollbar-thumb:hover{background:#2f9df0;background-clip:content-box;}
        ::-webkit-scrollbar-corner{background:transparent;}
        .ad-check-field{display:flex;align-items:center;gap:10px;border:1px solid var(--admin-input-border);background:var(--admin-input-bg);border-radius:13px;padding:12px 14px;cursor:pointer;}
        .ad-check-field input{width:18px;height:18px;min-height:0;accent-color:#3b82f6;} .ad-check-field span{font-size:.88rem;font-weight:600;}
        .ad-field-error{color:#fca5a5;font-size:.78rem;}

        /* Lang tabs */
        .ad-langtabs{display:flex;gap:6px;margin-bottom:12px;}
        .ad-langtab{padding:7px 16px;border-radius:11px;border:1px solid var(--admin-input-border);background:var(--admin-input-bg);color:var(--admin-muted);font-weight:700;font-size:.82rem;cursor:pointer;transition:.18s;}
        .ad-langtab.is-active{background:linear-gradient(135deg,#2563eb,#38bdf8);color:#fff;border-color:transparent;}
        .ad-langpane{display:none;} .ad-langpane.is-active{display:grid;gap:14px;}

        /* Upload */
        .ad-upload{display:grid;gap:10px;}
        .ad-upload__input{display:none;}
        .ad-upload__zone{display:flex;align-items:center;gap:14px;border:1.5px dashed rgba(130,156,196,.28);border-radius:16px;padding:16px;cursor:pointer;transition:.2s;}
        .ad-upload__zone:hover{border-color:rgba(59,130,246,.5);background:rgba(59,130,246,.05);}
        .ad-upload__icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:rgba(59,130,246,.12);color:#8ab4ff;font-size:1.1rem;flex:0 0 44px;}
        .ad-upload__text strong{display:block;font-size:.88rem;} .ad-upload__text span{font-size:.76rem;color:var(--admin-muted);}
        .ad-upload__preview{position:relative;border-radius:14px;overflow:hidden;border:1px solid var(--admin-border);max-width:220px;}
        .ad-upload__preview img{display:block;width:100%;height:auto;}
        .ad-upload__change{position:absolute;right:8px;bottom:8px;background:rgba(0,0,0,.6);color:#fff;border-radius:10px;padding:6px 10px;font-size:.76rem;cursor:pointer;}

        /* Gallery */
        .ad-gallery{display:flex;flex-wrap:wrap;gap:10px;}
        .ad-gallery__item{position:relative;width:104px;height:104px;border-radius:14px;overflow:hidden;border:1px solid var(--admin-border);}
        .ad-gallery__item img{width:100%;height:100%;object-fit:cover;}
        .ad-gallery__remove{position:absolute;top:4px;right:4px;width:24px;height:24px;border-radius:8px;border:none;background:rgba(239,68,68,.85);color:#fff;cursor:pointer;display:grid;place-items:center;font-size:.7rem;}

        /* Repeater */
        .ad-repeater{display:grid;gap:12px;}
        .ad-repeater__item{display:grid;grid-template-columns:42px minmax(0,1fr) 40px;gap:12px;align-items:start;border:1px solid rgba(130,156,196,.10);background:rgba(255,255,255,.028);border-radius:18px;padding:14px;}
        .ad-repeater__num{width:40px;height:40px;border-radius:13px;display:grid;place-items:center;color:#bfdbfe;font-weight:900;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.18);}
        .ad-repeater__fields{display:grid;gap:10px;}
        .ad-repeater__remove{width:40px;height:40px;border-radius:13px;border:1px solid rgba(239,68,68,.20);background:rgba(239,68,68,.08);color:#fecaca;cursor:pointer;}
        .ad-repeater__remove:hover{background:rgba(239,68,68,.16);color:#fff;}

        /* Tables */
        .ad-table-wrap{overflow-x:auto;border-radius:var(--radius-lg);}
        table.ad-table{width:100%;border-collapse:collapse;font-size:.9rem;}
        table.ad-table th{text-align:left;padding:14px 16px;color:#9fb1cf;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--admin-border);}
        table.ad-table td{padding:13px 16px;border-bottom:1px solid rgba(130,156,196,.08);vertical-align:middle;}
        table.ad-table tr:hover td{background:rgba(59,130,246,.04);}
        .ad-thumb{width:48px;height:48px;border-radius:11px;object-fit:cover;border:1px solid var(--admin-border);background:rgba(255,255,255,.04);}
        .ad-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:9px;font-size:.76rem;font-weight:700;}
        .ad-pagination{display:flex;flex-wrap:wrap;gap:6px;align-items:center;list-style:none;margin:0;padding:0;}
        .ad-pagination li{margin:0;}
        .ad-pagination .page-link,.ad-pagination .page-dots{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 11px;border:1px solid var(--admin-border);background:var(--admin-surface);color:var(--admin-text);border-radius:10px;font-size:.86rem;font-weight:600;cursor:pointer;text-decoration:none;transition:.15s;}
        .ad-pagination button.page-link:hover{border-color:var(--admin-blue);color:var(--admin-blue);}
        .ad-pagination .is-active .page-link{background:var(--admin-blue);border-color:var(--admin-blue);color:#fff;cursor:default;}
        .ad-pagination .is-disabled .page-link{opacity:.4;cursor:not-allowed;}
        .ad-pagination .page-dots{border:none;background:transparent;cursor:default;min-width:auto;}
        .ad-badge--on{background:rgba(34,197,94,.14);color:#86efac;border:1px solid rgba(34,197,94,.22);}
        .ad-badge--off{background:rgba(148,163,184,.14);color:#cbd5e1;border:1px solid rgba(148,163,184,.22);}
        .ad-badge--warn{background:rgba(245,158,11,.14);color:#fcd34d;border:1px solid rgba(245,158,11,.22);}
        [data-admin-theme="light"] .ad-badge--on{background:#dcfce7;color:#15803d;border-color:#86efac;}
        [data-admin-theme="light"] .ad-badge--off{background:#eef1f6;color:#475569;border-color:#cbd5e1;}
        [data-admin-theme="light"] .ad-badge--warn{background:#fef3c7;color:#b45309;border-color:#fcd34d;}
        .ad-row-actions{display:flex;gap:8px;justify-content:flex-end;}
        .ad-icon-btn{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;border:1px solid var(--admin-border);background:rgba(255,255,255,.04);color:#c8d5ef;cursor:pointer;transition:.18s;}
        .ad-icon-btn:hover{border-color:rgba(59,130,246,.4);color:#fff;background:rgba(59,130,246,.12);}
        .ad-icon-btn.danger:hover{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.14);color:#fff;}
        .ad-search{display:flex;align-items:center;gap:10px;background:var(--admin-input-bg);border:1px solid var(--admin-input-border);border-radius:13px;padding:0 14px;min-width:260px;}
        .ad-search input{border:none;background:none;color:var(--admin-input-text);min-height:44px;width:100%;outline:none;font-size:.9rem;}
        .ad-search i{color:var(--admin-muted);}
        .ad-empty{text-align:center;padding:48px 20px;color:var(--admin-muted);}
        .ad-empty i{font-size:2.4rem;opacity:.4;margin-bottom:12px;display:block;}

        /* SEO preview snippet */
        .ad-serp{border:1px solid var(--admin-border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.025);}
        .ad-serp__url{color:#86efac;font-size:.78rem;} .ad-serp__title{color:#8ab4ff;font-size:1.05rem;margin:3px 0;font-weight:600;} .ad-serp__desc{color:var(--admin-muted);font-size:.83rem;line-height:1.45;}
        .ad-counter{font-size:.74rem;color:var(--admin-muted);} .ad-counter.over{color:#fca5a5;}

        .ad-footer{color:#6f83a5;font-size:.82rem;padding:0 28px 28px;}

        /* Summernote dark */
        .note-editor.note-frame{border:1px solid rgba(130,156,196,.15)!important;border-radius:14px!important;overflow:hidden;background:rgba(255,255,255,.045)!important;}
        .note-editor .note-toolbar{background:rgba(255,255,255,.03)!important;border-bottom:1px solid rgba(130,156,196,.12)!important;padding:8px 10px!important;display:flex!important;flex-wrap:wrap!important;gap:6px!important;}
        .note-editor .note-toolbar .note-btn{background:rgba(255,255,255,.06)!important;border:1px solid rgba(130,156,196,.12)!important;color:#c8d5ef!important;border-radius:10px!important;height:32px;min-width:32px;}
        .note-editor .note-toolbar .note-btn:hover{background:rgba(59,130,246,.15)!important;color:#fff!important;}
        .note-editor .note-editing-area .note-editable{background:transparent!important;color:#e8edf4!important;padding:14px!important;min-height:120px;line-height:1.7;}
        .note-btn-group{display:inline-flex!important;gap:3px!important;}
        .note-dropdown-menu{background:#0f2037!important;border:1px solid rgba(130,156,196,.18)!important;border-radius:12px!important;}
        [data-admin-theme="light"] .note-editor.note-frame{background:#f7f9fc!important;border-color:rgba(0,0,0,.10)!important;}
        [data-admin-theme="light"] .note-editor .note-toolbar{background:#fff!important;}
        [data-admin-theme="light"] .note-editor .note-toolbar .note-btn{background:#f4f7fb!important;color:#374151!important;}
        [data-admin-theme="light"] .note-editor .note-editing-area .note-editable{color:#0f1729!important;}

        /* Toast */
        .ad-toast-container{position:fixed;top:24px;right:24px;z-index:10000;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
        .ad-toast{pointer-events:auto;min-width:300px;max-width:440px;padding:15px 18px;border-radius:16px;display:flex;align-items:flex-start;gap:12px;font-size:.88rem;font-weight:600;line-height:1.45;box-shadow:0 20px 50px rgba(0,0,0,.25);transform:translateX(120%);opacity:0;transition:transform .5s cubic-bezier(.34,1.56,.64,1),opacity .4s;position:relative;}
        .ad-toast.is-visible{transform:translateX(0);opacity:1;}
        .ad-toast--success{background:linear-gradient(135deg,#065f46,#047857);color:#d1fae5;border:1px solid rgba(52,211,153,.25);}
        .ad-toast--error{background:linear-gradient(135deg,#7f1d1d,#991b1b);color:#fee2e2;border:1px solid rgba(252,165,165,.25);}
        .ad-toast__icon{width:22px;height:22px;flex:0 0 22px;margin-top:1px;}
        .ad-toast__close{margin-left:auto;background:none;border:none;color:inherit;opacity:.5;cursor:pointer;font-size:1.1rem;}
        .ad-toast__progress{position:absolute;bottom:0;left:0;height:3px;background:rgba(255,255,255,.3);}

        @media (max-width:1100px){.ad-form-layout{grid-template-columns:1fr;}.ad-form-side{position:static;}}
        @media (max-width:860px){.ad-admin-shell{padding-left:0;}.ad-sidebar{position:static;width:auto;}.ad-topbar{position:static;height:auto;padding:18px;}.ad-content{padding:18px;}.ad-fields-grid.two,.ad-fields-grid.three{grid-template-columns:1fr;}.ad-profile-info{display:none;}}
        .ad-tree-toggle{width:24px;height:24px;min-width:24px;border-radius:7px;border:1px solid var(--admin-border);background:rgba(0,0,0,.03);color:var(--admin-muted);cursor:pointer;display:grid;place-items:center;font-size:.62rem;transition:.18s;padding:0;}
        .ad-tree-toggle:hover{border-color:rgba(59,130,246,.4);color:var(--admin-blue);}
        .ad-tree-toggle--empty{border-color:transparent;background:transparent;cursor:default;}
        [data-admin-theme="dark"] .ad-tree-toggle{background:rgba(255,255,255,.045);}
    </style>
    @livewireStyles
    @stack('styles')
</head>
<body>
    <div class="ad-admin-shell">
        <aside class="ad-sidebar">
            <a wire:navigate href="{{ route('dashboard') }}" class="ad-brand ad-brand--logo">
                <svg class="ad-brand-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 481.59 94.28">
                    <g>
                        <path fill="currentColor" d="M47,94.28A46.82,46.82,0,0,0,94.15,47.14,46.74,46.74,0,0,0,47,0,46.63,46.63,0,0,0,0,47.14,46.71,46.71,0,0,0,47,94.28ZM47,76.8c-16.45,0-29.27-12.31-29.27-29.66S30.56,17.35,47,17.35,76.28,29.66,76.28,47.14,63.46,76.8,47,76.8Z"/>
                        <path fill="currentColor" d="M140.5,1.81H106.7V92.47h17.87V62.68H140.5c17.48,0,30.95-13.47,30.95-30.43S158,1.81,140.5,1.81Zm0,44.16H124.57V18.52H140.5c7.64,0,13.21,5.83,13.21,13.73S148.14,46,140.5,46Z"/>
                        <path fill="currentColor" d="M241.38,1.81h-66.7v17.1H199V92.47H216.9V18.91h24.48Z"/>
                        <path fill="currentColor" d="M362.32,94.28c16.71,0,31.34-8.42,39-21.37L385.89,64c-4.27,7.9-13.21,12.83-23.57,12.83-17.74,0-29.39-12.31-29.39-29.66s11.65-29.79,29.39-29.79c10.36,0,19.17,4.92,23.57,13l15.41-8.93C393.53,8.42,378.9,0,362.32,0c-27.45,0-47.14,20.59-47.14,47.14S334.87,94.28,362.32,94.28Z"/>
                        <path fill="currentColor" d="M463.85,1.81v36H430.17v-36H412.3V92.47h17.87V54.91h33.68V92.47h17.74V1.81Z"/>
                        <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,1.77h39V19h-39Z"/>
                        <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,37.77h48V55h-48Z"/>
                        <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,75.26H309V92.51H252.79Z"/>
                    </g>
                </svg>
                <div class="ad-brand-subtitle">Панель управления</div>
            </a>

            <div class="ad-nav-label">Каталог</div>
            <nav class="ad-nav" id="adMainNav">
                <a wire:navigate class="ad-nav-link {{ request()->is('dashboard') ? 'is-active' : '' }}" data-match="/dashboard" href="{{ route('dashboard') }}"><span class="ad-nav-icon"><i class="fas fa-th-large"></i></span><span>Дашборд</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('categor*', 'category*') ? 'is-active' : '' }}" data-match="/categor" href="{{ route('categories') }}"><span class="ad-nav-icon"><i class="fas fa-sitemap"></i></span><span>Категории</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('product*') ? 'is-active' : '' }}" data-match="/product" href="{{ route('products') }}"><span class="ad-nav-icon"><i class="fas fa-box"></i></span><span>Товары</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('brand*') ? 'is-active' : '' }}" data-match="/brand" href="{{ route('brands') }}"><span class="ad-nav-icon"><i class="fas fa-tag"></i></span><span>Бренды</span></a>
            </nav>

            <div class="ad-nav-label">Контент</div>
            <nav class="ad-nav">
                <a wire:navigate class="ad-nav-link {{ request()->is('article*') ? 'is-active' : '' }}" data-match="/article" href="{{ route('articles') }}"><span class="ad-nav-icon"><i class="fas fa-newspaper"></i></span><span>Статьи</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('solcategor*', 'solcategory*') ? 'is-active' : '' }}" data-match="/solcategor" href="{{ route('solcategories') }}"><span class="ad-nav-icon"><i class="fas fa-cubes"></i></span><span>Категории решений</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('solution*') ? 'is-active' : '' }}" data-match="/solution" href="{{ route('solutions') }}"><span class="ad-nav-icon"><i class="fas fa-layer-group"></i></span><span>Решения</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('project*') ? 'is-active' : '' }}" data-match="/project" href="{{ route('projects') }}"><span class="ad-nav-icon"><i class="fas fa-briefcase"></i></span><span>Проекты</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('service*') ? 'is-active' : '' }}" data-match="/service" href="{{ route('services') }}"><span class="ad-nav-icon"><i class="fas fa-cogs"></i></span><span>Услуги</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('offer*') ? 'is-active' : '' }}" data-match="/offer" href="{{ route('offers') }}"><span class="ad-nav-icon"><i class="fas fa-percent"></i></span><span>Спецпредложения</span></a>
            </nav>

            <div class="ad-nav-label">Сайт</div>
            <nav class="ad-nav">
                <a wire:navigate class="ad-nav-link {{ request()->is('slider*') ? 'is-active' : '' }}" data-match="/slider" href="{{ route('sliders') }}"><span class="ad-nav-icon"><i class="fas fa-images"></i></span><span>Слайдеры</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('partner*') ? 'is-active' : '' }}" data-match="/partner" href="{{ route('partners') }}"><span class="ad-nav-icon"><i class="fas fa-handshake"></i></span><span>Партнёры</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('license*') ? 'is-active' : '' }}" data-match="/license" href="{{ route('licenses') }}"><span class="ad-nav-icon"><i class="fas fa-certificate"></i></span><span>Лицензии</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('setting*') ? 'is-active' : '' }}" data-match="/setting" href="{{ route('settings') }}"><span class="ad-nav-icon"><i class="fas fa-sliders-h"></i></span><span>Настройки и SEO</span></a>
                <a wire:navigate class="ad-nav-link {{ request()->is('redirect*') ? 'is-active' : '' }}" data-match="/redirect" href="{{ route('redirects') }}"><span class="ad-nav-icon"><i class="fas fa-route"></i></span><span>Редиректы</span></a>
            </nav>

        </aside>

        <main class="ad-main">
            <header class="ad-topbar">
                <div>
                    <nav class="ad-breadcrumbs" aria-label="Навигация">
                        <a wire:navigate href="{{ route('dashboard') }}"><i class="fas fa-th-large"></i></a>
                        <span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>
                        @yield('breadcrumbs')<span class="ad-breadcrumbs__current">@yield('page-title', 'Панель')</span>
                    </nav>
                    <h2 class="ad-page-title">@yield('page-title', 'Административная панель')</h2>
                </div>
                <div class="ad-profile">
                    <button type="button" class="ad-theme-toggle" id="themeToggle" title="Переключить тему">
                        <i class="fas fa-sun"></i><i class="fas fa-moon"></i>
                    </button>
                    <div class="ad-profile-avatar">{{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}</div>
                    <div class="ad-profile-info">
                        <div class="ad-profile-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                        <div class="ad-profile-role">{{ auth()->user()->email ?? 'admin' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="ad-logout-btn" type="submit" title="Выйти"><i class="fas fa-sign-out-alt"></i></button></form>
                </div>
            </header>

            <section class="ad-content" id="adContent">
                {{ $slot ?? '' }}
                @yield('content')
            </section>
            <footer class="ad-footer">© {{ date('Y') }} OPTECH.kz — административная панель.</footer>
        </main>

        <div class="ad-toast-container" id="adToastContainer"></div>
    </div>

    @livewireScripts
    @stack('scripts')
    <script>

        (function () {
            var container = document.getElementById('adToastContainer');
            window.adToast = function (message, type, duration) {
                type = type || 'success'; duration = duration || 4500;
                var toast = document.createElement('div');
                toast.className = 'ad-toast ad-toast--' + type;
                toast.innerHTML =
                    '<span class="ad-toast__icon">' +
                    (type === 'success'
                        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9.5"/></svg>'
                        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>') +
                    '</span><span>' + message + '</span>' +
                    '<button class="ad-toast__close" onclick="this.parentElement.classList.replace(\'is-visible\',\'is-hiding\');var t=this.parentElement;setTimeout(function(){t.remove()},400)">&times;</button>' +
                    '<span class="ad-toast__progress" style="width:100%;transition:width ' + duration + 'ms linear"></span>';
                container.appendChild(toast);
                requestAnimationFrame(function () { requestAnimationFrame(function () {
                    toast.classList.add('is-visible');
                    toast.querySelector('.ad-toast__progress').style.width = '0%';
                }); });
                setTimeout(function () { toast.classList.add('is-hiding'); toast.classList.remove('is-visible'); setTimeout(function(){toast.remove();}, 400); }, duration);
            };
            @if(session('success')) adToast(@json(session('success')), 'success'); @endif
            @if(session('error')) adToast(@json(session('error')), 'error', 6000); @endif
            @if($errors->any()) adToast(@json($errors->first()), 'error', 6000); @endif
        })();

        document.addEventListener('livewire:init', function () {
            Livewire.on('toast', function (data) {
                var d = Array.isArray(data) ? data[0] : data;
                if (window.adToast) adToast(d.message || 'Готово!', d.type || 'success', d.duration || 4500);
            });
        });

        /* Rich text editor bridge for Livewire ([data-richtext] + wire:model) */
        function initRichText(scope) {
            (scope || document).querySelectorAll('textarea[data-richtext]').forEach(function (el) {
                if (el.dataset.snInit) return;
                el.dataset.snInit = '1';
                var $el = $(el);
                $el.summernote({
                    height: 180, lang: 'ru-RU', placeholder: 'Начните писать...', disableDragAndDrop: true,
                    toolbar: [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link', 'picture']], ['view', ['codeview']]],
                    styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote'],
                    callbacks: {
                        onChange: function (contents) {
                            el.value = contents;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                });
            });
        }

        /* КРИТИЧНО: уничтожаем Summernote ДО навигации, иначе его инъекция в DOM
           попадает в bfcache-снапшот wire:navigate, ломает морфинг и Livewire
           откатывается на предыдущую страницу («перескок назад»). */
        function destroyRichText(scope) {
            (scope || document).querySelectorAll('textarea[data-richtext]').forEach(function (el) {
                if (!el.dataset.snInit) return;
                var $el = $(el);
                // синхронизируем значение обратно в textarea перед разрушением
                if ($el.summernote) { el.value = $el.summernote('code'); $el.summernote('destroy'); }
                delete el.dataset.snInit;
            });
        }

        document.addEventListener('DOMContentLoaded', function(){ initRichText(); });
        document.addEventListener('livewire:navigating', function(){ destroyRichText(); });
        document.addEventListener('livewire:navigated', function(){ initRichText(); });

        /* SEO char counters */
        function bindCounters(scope){
            (scope||document).querySelectorAll('[data-counter]').forEach(function(inp){
                if(inp.dataset.cntInit) return; inp.dataset.cntInit='1';
                var max=parseInt(inp.dataset.counter,10);
                var out=document.querySelector('[data-counter-for="'+inp.getAttribute('name')+'"]');
                if(!out) return;
                function upd(){var l=inp.value.length;out.textContent=l+' / '+max;out.classList.toggle('over',l>max);}
                inp.addEventListener('input',upd);upd();
            });
        }
        document.addEventListener('DOMContentLoaded', function(){ bindCounters(); });
        document.addEventListener('livewire:navigated', function(){ bindCounters(); });

        /* Lang tabs (event delegation) */
        document.addEventListener('click', function(e){
            var tab = e.target.closest('.ad-langtab'); if(!tab) return;
            var group = tab.closest('[data-langtabs]'); if(!group) return;
            group.querySelectorAll('.ad-langtab').forEach(function(t){t.classList.remove('is-active');});
            group.querySelectorAll('.ad-langpane').forEach(function(p){p.classList.remove('is-active');});
            tab.classList.add('is-active');
            var pane = group.querySelector('.ad-langpane[data-lang="'+tab.dataset.lang+'"]');
            if(pane) pane.classList.add('is-active');
        });

        function applyStoredTheme(){var t=localStorage.getItem('admin-theme');if(t)document.documentElement.setAttribute('data-admin-theme',t);}

        /* Переключатель темы.
           1) Слушатель вешается на document РОВНО ОДИН РАЗ (guard __themeBound) —
              document переживает wire:navigate, повторно привязывать не нужно.
           2) Дебаунс по времени (__themeTs): если по какой-то причине сработает
              два обработчика на один клик (напр. в кэше остался старый слушатель),
              второй вызов в пределах 200мс игнорируется. Это исключает эффект
              «тема переключается через раз». */
        function toggleTheme(){
            var now = Date.now();
            if (now - (window.__themeTs || 0) < 200) return;   // защита от двойного срабатывания
            window.__themeTs = now;
            var html = document.documentElement;
            var next = html.getAttribute('data-admin-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-admin-theme', next);
            localStorage.setItem('admin-theme', next);
        }
        if (!window.__themeBound) {
            window.__themeBound = true;
            document.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('#themeToggle')) toggleTheme();
            });
        }

        applyStoredTheme();
        document.addEventListener('livewire:navigated', function(){ applyStoredTheme(); });
    </script>
</body>
</html>