@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Plays</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Plays</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
        <div class="pv-page container-fluid">
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="{{ route('play.create') }}" class="pv-btn pv-btn--primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Upload Play
                    </a>
                </div>
            </div>

            <!-- Shared search + filters toolbar -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="pv-toolbar">
                        <div class="pv-toolbar__row">
                            <div class="pv-search">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="text" id="playSearchInput" class="pv-search__input" placeholder="Search play titles&hellip;">
                            </div>

                            <div class="pv-dropdown" data-dropdown="role">
                                <button type="button" class="pv-dropdown__toggle">
                                    <span class="pv-dropdown__value">All Packages</span>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <ul class="pv-dropdown__list" hidden>
                                    <li class="pv-dropdown__option pv-dropdown__option--active" data-value="">All Packages</li>
                                    <li class="pv-dropdown__option" data-value="1">Classic basic</li>
                                    <li class="pv-dropdown__option" data-value="2">Classic advance</li>
                                    <li class="pv-dropdown__option" data-value="3">HD Human Dashboard</li>
                                    <li class="pv-dropdown__option" data-value="4">Pro basic</li>
                                    <li class="pv-dropdown__option" data-value="5">Pro advance</li>
                                </ul>
                                <input type="hidden" id="roleFilter" value="">
                            </div>

                            <div class="pv-popover">
                                <button type="button" id="playFiltersToggle" class="pv-btn pv-btn--outline">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                                    </svg>
                                    Filters
                                    <span id="playFiltersBadge" class="pv-badge" style="display:none;"></span>
                                </button>

                                <div id="playFiltersPanel" class="pv-filters-panel" hidden>
                                    <div class="pv-filter-group">
                                        <div class="pv-filter-group__header">
                                            <span class="pv-filter-group__title">Strategies</span>
                                            <span class="pv-filter-group__links">
                                                <a href="#" class="play-filter-select-all" data-group="strategies">All</a><span class="pv-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="strategies">None</a>
                                            </span>
                                        </div>
                                        <div class="pv-filter-pills">
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="regular"><span>Regular</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="red zone"><span>Red Zone</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="hurry up"><span>Hurry Up</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="aggressive"><span>Aggressive</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="chew clock"><span>Chew Clock</span></label>
                                        </div>
                                    </div>

                                    <div class="pv-filter-group">
                                        <div class="pv-filter-group__header">
                                            <span class="pv-filter-group__title">Preferred Down</span>
                                            <span class="pv-filter-group__links">
                                                <a href="#" class="play-filter-select-all" data-group="preferred_down">All</a><span class="pv-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="preferred_down">None</a>
                                            </span>
                                        </div>
                                        <div class="pv-filter-pills">
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="1"><span>1st</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="2"><span>2nd</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="3"><span>3rd</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="4"><span>4th</span></label>
                                        </div>
                                    </div>

                                    <div class="pv-filter-group">
                                        <div class="pv-filter-group__header">
                                            <span class="pv-filter-group__title">Yardage Gain</span>
                                            <span class="pv-filter-group__links">
                                                <a href="#" class="play-filter-select-all" data-group="yardage_gain">All</a><span class="pv-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="yardage_gain">None</a>
                                            </span>
                                        </div>
                                        <div class="pv-filter-pills">
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="short"><span>Short (1-4)</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="medium"><span>Medium (4-7)</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="long"><span>Long (7+)</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="open_down"><span>Open Down</span></label>
                                        </div>
                                    </div>

                                    <div class="pv-filter-group">
                                        <div class="pv-filter-group__header">
                                            <span class="pv-filter-group__title">Play Target</span>
                                            <span class="pv-filter-group__links">
                                                <a href="#" class="play-filter-select-all" data-group="play_target">All</a><span class="pv-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="play_target">None</a>
                                            </span>
                                        </div>
                                        <div class="pv-filter-pills">
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="play_target" value="offensive"><span>Offensive</span></label>
                                            <label class="pv-chip-toggle"><input type="checkbox" class="play-filter-checkbox" data-group="play_target" value="defensive"><span>Defensive</span></label>
                                        </div>
                                    </div>

                                    <div class="pv-filter-footer">
                                        <button type="button" id="clearAllPlayFiltersBtn" class="pv-btn pv-btn--ghost-danger">Clear all filters</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="playFiltersChips" class="pv-chips"></div>
                    </div>
                </div>
            </div>

            <!-- Global Plays / League Plays, side by side -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="pv-panel" id="globalPlaysPanel">
                        <div class="pv-panel__header">
                            <div>
                                <h3 class="pv-panel__title">Global Plays</h3>
                                <p class="pv-panel__subtitle">Shared across every league. "Starter" controls which ones a new league sees by default. Drag a League Play here to copy it into Global Plays.</p>
                            </div>
                        </div>

                        <div class="pv-starter-bar">
                            <div class="pv-starter-bar__scope">
                                <span class="pv-starter-bar__label">Apply to</span>
                                <div class="pv-seg">
                                    <button type="button" class="pv-seg__option pv-seg__option--active" data-scope="page">This page</button>
                                    <button type="button" class="pv-seg__option" data-scope="all">All matching</button>
                                </div>
                                <span id="starterScopeCount" class="pv-starter-bar__count"></span>
                            </div>
                            <div class="pv-starter-bar__actions">
                                <button type="button" id="markStarterBtn" class="pv-btn pv-btn--success-soft">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Mark as Starter
                                </button>
                                <button type="button" id="removeStarterBtn" class="pv-btn pv-btn--ghost-danger">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Remove Starter
                                </button>
                            </div>
                        </div>

                        <table class="table table-sm table-hover pv-table data-table-global">
                            <thead>
                            <tr>
                                <th width="24px"></th>
                                <th>No</th>
                                <th>Play Name</th>
                                <th>Yardage</th>
                                <th>Down</th>
                                <th>Possession</th>
                                <th>Strategies</th>
                                <th width="64px">Starter</th>
                                <th width="64px">Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="pv-panel" id="leaguePlaysPanel">
                        <div class="pv-panel__header">
                            <div>
                                <h3 class="pv-panel__title">League Plays</h3>
                                <p class="pv-panel__subtitle">Belong to one specific league only &mdash; not visible anywhere else. Drag a Global Play here to copy it into a league.</p>
                            </div>
                        </div>
                        <table class="table table-sm table-hover pv-table data-table-league">
                            <thead>
                            <tr>
                                <th width="24px"></th>
                                <th>No</th>
                                <th>Play Name</th>
                                <th>League</th>
                                <th>Yardage</th>
                                <th>Down</th>
                                <th>Possession</th>
                                <th>Strategies</th>
                                <th width="64px">Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="copyPlayModal" class="pv-modal-backdrop" hidden>
                <div class="pv-modal">
                    <h4 class="pv-modal__title">Copy Play</h4>
                    <p id="copyPlayModalDesc" class="pv-modal__desc"></p>
                    <div id="copyPlayLeagueField" class="pv-modal__field" hidden>
                        <label class="pv-filter-group__title" for="copyPlayLeagueSelect">League</label>
                        <select id="copyPlayLeagueSelect" class="pv-native-select">
                            <option value="">Choose a league&hellip;</option>
                            @foreach($leagues ?? [] as $league)
                                <option value="{{ $league->id }}">{{ $league->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pv-modal__actions">
                        <button type="button" id="copyPlayCancelBtn" class="pv-btn pv-btn--outline">Cancel</button>
                        <button type="button" id="copyPlayConfirmBtn" class="pv-btn pv-btn--primary">Copy</button>
                    </div>
                </div>
            </div>

            <div id="pvToastContainer" class="pv-toast-container" aria-live="polite"></div>
        </div><!-- /.container-fluid -->
    </section>

    <style>
        .pv-page {
            --pv-accent: #4F46E5;
            --pv-accent-dark: #4338CA;
            --pv-accent-soft: #EEF2FF;
            --pv-success: #0D9488;
            --pv-success-soft: #ECFDF5;
            --pv-success-text: #047857;
            --pv-danger: #E11D48;
            --pv-danger-soft: #FEF2F2;
            --pv-border: #E5E7EB;
            --pv-text: #111827;
            --pv-text-muted: #6B7280;
            --pv-radius: 14px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, Roboto, Helvetica, Arial, sans-serif;
            color: var(--pv-text);
        }

        .pv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 14px;
            cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap;
            line-height: 1;
            transition: background .15s, border-color .15s, color .15s;
        }
        .pv-btn--primary { background: var(--pv-accent); color: #fff; }
        .pv-btn--primary:hover { background: var(--pv-accent-dark); color: #fff; }
        .pv-btn--outline { background: #ffffff; border-color: var(--pv-border); color: var(--pv-text-muted); }
        .pv-btn--outline:hover { border-color: var(--pv-accent); color: var(--pv-accent); }
        .pv-btn--success-soft { background: var(--pv-success-soft); color: var(--pv-success-text); }
        .pv-btn--success-soft:hover { background: var(--pv-success); color: #fff; }
        .pv-btn--ghost-danger { background: none; border-color: var(--pv-border); color: var(--pv-danger); }
        .pv-btn--ghost-danger:hover { background: var(--pv-danger-soft); border-color: var(--pv-danger); }

        .pv-badge {
            background: var(--pv-accent);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 999px;
            min-width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        /* Toolbar */
        .pv-toolbar {
            background: #ffffff;
            border: 1px solid var(--pv-border);
            border-radius: var(--pv-radius);
            padding: 12px 14px;
            box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
        }
        .pv-toolbar__row { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }

        .pv-search { position: relative; flex: 1 1 260px; min-width: 200px; }
        .pv-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .pv-search__input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1px solid var(--pv-border);
            border-radius: 9px;
            font-size: 13px;
            color: var(--pv-text);
        }
        .pv-search__input:focus { outline: none; border-color: var(--pv-accent); box-shadow: 0 0 0 3px var(--pv-accent-soft); }

        /* Generic custom dropdown (Role / Yardage / Play Target) */
        .pv-dropdown { position: relative; }
        .pv-dropdown__toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            min-width: 150px;
            padding: 9px 12px;
            border: 1px solid var(--pv-border);
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            color: var(--pv-text);
            background: #ffffff;
            cursor: pointer;
        }
        .pv-dropdown__toggle:hover { border-color: var(--pv-accent); }
        .pv-dropdown__toggle svg { flex-shrink: 0; color: #9ca3af; }
        .pv-dropdown__list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 40;
            background: #ffffff;
            border: 1px solid var(--pv-border);
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.12);
            list-style: none;
            margin: 0;
            padding: 4px;
            max-height: 220px;
            overflow-y: auto;
        }
        .pv-dropdown__option { padding: 8px 10px; font-size: 13px; color: var(--pv-text); border-radius: 7px; cursor: pointer; white-space: nowrap; }
        .pv-dropdown__option:hover { background: var(--pv-accent-soft); color: var(--pv-accent); }
        .pv-dropdown__option--active { background: var(--pv-accent); color: #fff; }

        /* Filters popover */
        .pv-popover { position: relative; }
        .pv-filters-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 35;
            width: 560px;
            max-width: calc(100vw - 48px);
            background: #ffffff;
            border: 1px solid var(--pv-border);
            border-radius: var(--pv-radius);
            box-shadow: 0 16px 40px rgba(17, 24, 39, 0.14);
            padding: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }
        .pv-filter-group { min-width: 200px; flex: 1 1 200px; }
        .pv-filter-group__header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
        .pv-filter-group__title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--pv-text-muted);
            white-space: nowrap;
            display: block;
            margin-bottom: 8px;
        }
        .pv-filter-group__links { font-size: 11.5px; white-space: nowrap; }
        .pv-filter-group__links a { color: var(--pv-accent); text-decoration: none; font-weight: 600; }
        .pv-filter-group__links a:hover { text-decoration: underline; }
        .pv-filter-group__sep { margin: 0 4px; color: #d1d5db; }

        .pv-filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }
        .pv-chip-toggle {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border: 1px solid var(--pv-border);
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--pv-text-muted);
            cursor: pointer;
            user-select: none;
            transition: background .15s, border-color .15s, color .15s;
        }
        .pv-chip-toggle input { display: none; }
        .pv-chip-toggle--active { background: var(--pv-accent); border-color: var(--pv-accent); color: #ffffff; }

        .pv-filter-footer { flex-basis: 100%; display: flex; justify-content: flex-end; border-top: 1px solid var(--pv-border); padding-top: 12px; }

        .pv-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 10px; }
        .pv-chips:empty { margin-top: 0; }
        .pv-chips__clear-all {
            color: var(--pv-danger);
            font-size: 12px;
            font-weight: 600;
            text-decoration: underline;
            margin-left: 2px;
        }
        .pv-chips__clear-all:hover { color: var(--pv-danger); }
        .pv-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--pv-accent-soft);
            color: var(--pv-accent);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px 4px 12px;
        }
        .pv-chip__remove { color: inherit; text-decoration: none; font-weight: 700; line-height: 1; padding: 0 2px; }
        .pv-chip__remove:hover { color: var(--pv-danger); }

        /* Panels */
        .pv-panel {
            background: #ffffff;
            border: 1px solid var(--pv-border);
            border-radius: var(--pv-radius);
            padding: 16px 18px;
            height: 100%;
            box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
        }
        .pv-panel__header { margin-bottom: 12px; }
        .pv-panel__title { font-size: 15px; font-weight: 700; margin: 0; color: var(--pv-text); }
        .pv-panel__subtitle { font-size: 12px; color: var(--pv-text-muted); margin: 2px 0 0; }

        /* Starter bulk-action bar */
        .pv-starter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            background: #F9FAFB;
            border: 1px solid var(--pv-border);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .pv-starter-bar__scope { display: flex; align-items: center; gap: 8px; }
        .pv-starter-bar__label { font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--pv-text-muted); }
        .pv-starter-bar__count { font-size: 12px; color: var(--pv-text-muted); }
        .pv-starter-bar__actions { display: flex; gap: 8px; }

        .pv-seg { display: inline-flex; background: #ffffff; border: 1px solid var(--pv-border); border-radius: 999px; padding: 2px; }
        .pv-seg__option {
            border: none;
            background: none;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--pv-text-muted);
            border-radius: 999px;
            cursor: pointer;
        }
        .pv-seg__option--active { background: var(--pv-accent); color: #ffffff; }

        /* Starter switch (per row) */
        .pv-switch { position: relative; display: inline-block; width: 32px; height: 19px; cursor: pointer; }
        .pv-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .pv-switch__slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 999px; transition: background .15s; }
        .pv-switch__slider::before {
            content: "";
            position: absolute;
            width: 13px;
            height: 13px;
            left: 3px;
            top: 3px;
            background: #ffffff;
            border-radius: 50%;
            transition: transform .15s;
            box-shadow: 0 1px 2px rgba(0,0,0,.25);
        }
        .pv-switch input:checked + .pv-switch__slider { background: var(--pv-success); }
        .pv-switch input:checked + .pv-switch__slider::before { transform: translateX(13px); }

        .pv-table { font-size: 12.5px; }
        .pv-table th { white-space: nowrap; color: var(--pv-text-muted); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
        .pv-table .btn { border-radius: 7px; }
        .pv-table td { white-space: nowrap; }

        .pv-pill-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .3px;
        }
        .pv-pill-badge--off { background: var(--pv-accent-soft); color: var(--pv-accent); }
        .pv-pill-badge--def { background: #FFF1E6; color: #C2410C; }

        .pv-truncate {
            display: inline-block;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }

        /* Stack the "Showing x of y" info and pagination as their own full-width rows -
           side by side in two half columns breaks once the panel narrows (two-up layout). */
        .pv-table-footer { margin-top: 10px; }
        .pv-table-footer__info { font-size: 12.5px; color: var(--pv-text-muted); margin-bottom: 8px; }
        .pv-table-footer__pagination .pagination { flex-wrap: wrap; row-gap: 6px; margin-bottom: 0; }

        /* DataTables' own horizontal-scroll area (scrollX) - keep it contained and tidy,
           leaving length/info/pagination controls outside it at full panel width. */
        .dataTables_scrollBody { border-bottom: 1px solid var(--pv-border); }
        .dataTables_scrollBody::-webkit-scrollbar { height: 8px; }
        .dataTables_scrollBody::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
        .dataTables_scrollBody::-webkit-scrollbar-track { background: transparent; }

        /* Drag-and-drop copy between Global Plays and League Plays */
        .pv-drag-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            color: #cbd5e1;
            cursor: grab;
            font-size: 13px;
            letter-spacing: -1px;
        }
        tr[draggable="true"]:hover .pv-drag-handle { color: var(--pv-accent); }
        tr[draggable="true"]:active { cursor: grabbing; }
        tr.pv-row-dragging { opacity: 0.4; }

        .pv-panel--drop-target { position: relative; }
        .pv-panel--drop-active {
            outline: 2px dashed var(--pv-accent);
            outline-offset: -2px;
            background: var(--pv-accent-soft);
        }

        .pv-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(17, 24, 39, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pv-modal {
            width: 360px;
            max-width: calc(100vw - 32px);
            background: #ffffff;
            border-radius: var(--pv-radius);
            padding: 20px;
            box-shadow: 0 24px 48px rgba(17, 24, 39, 0.25);
        }
        .pv-modal__title { font-size: 16px; font-weight: 700; margin: 0 0 6px; color: var(--pv-text); }
        .pv-modal__desc { font-size: 13px; color: var(--pv-text-muted); margin: 0 0 14px; }
        .pv-modal__field { margin-bottom: 16px; }
        .pv-native-select {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid var(--pv-border);
            border-radius: 9px;
            font-size: 13px;
            color: var(--pv-text);
            background: #ffffff;
        }
        .pv-native-select:focus { outline: none; border-color: var(--pv-accent); box-shadow: 0 0 0 3px var(--pv-accent-soft); }
        .pv-modal__actions { display: flex; justify-content: flex-end; gap: 8px; }

        .pv-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 340px;
            max-width: calc(100vw - 32px);
        }
        .pv-toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #ffffff;
            border: 1px solid var(--pv-border);
            border-left: 4px solid var(--pv-accent);
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(17, 24, 39, 0.16);
            padding: 12px 14px;
            font-size: 13px;
            color: var(--pv-text);
            opacity: 0;
            transform: translateX(16px);
            transition: opacity .2s ease, transform .2s ease;
        }
        .pv-toast--visible { opacity: 1; transform: translateX(0); }
        .pv-toast--success { border-left-color: var(--pv-success); }
        .pv-toast--error { border-left-color: var(--pv-danger); }
        .pv-toast__icon { flex-shrink: 0; margin-top: 1px; }
        .pv-toast--success .pv-toast__icon { color: var(--pv-success); }
        .pv-toast--error .pv-toast__icon { color: var(--pv-danger); }
        .pv-toast--info .pv-toast__icon { color: var(--pv-accent); }
        .pv-toast__message { flex: 1; line-height: 1.4; }
        .pv-toast__close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 15px;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
        }
        .pv-toast__close:hover { color: var(--pv-text); }
    </style>
@endsection

@section('script')
    <script type="text/javascript">
        $(function () {
            function getCheckedFilterValues(group) {
                return $('.play-filter-checkbox[data-group="' + group + '"]:checked')
                    .map(function () { return $(this).val(); })
                    .get();
            }

            function debounce(fn, wait) {
                let timer = null;
                return function () {
                    const args = arguments;
                    const ctx = this;
                    clearTimeout(timer);
                    timer = setTimeout(function () { fn.apply(ctx, args); }, wait);
                };
            }

            // jQuery only auto-populates xhr.responseJSON in some cases (depends on the
            // response's exact Content-Type negotiation); parsing responseText ourselves
            // works regardless of that, so server-provided error messages always show up.
            function errorMessage(xhr, fallback) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data && data.message) { return data.message; }
                } catch (e) { /* not JSON, fall through */ }
                return fallback;
            }

            const TOAST_ICONS = {
                success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            };

            function showToast(message, type) {
                type = TOAST_ICONS[type] ? type : 'info';
                const toast = $(
                    '<div class="pv-toast pv-toast--' + type + '">'
                    + '<span class="pv-toast__icon">' + TOAST_ICONS[type] + '</span>'
                    + '<span class="pv-toast__message"></span>'
                    + '<button type="button" class="pv-toast__close">&times;</button>'
                    + '</div>'
                );
                toast.find('.pv-toast__message').text(message);
                $('#pvToastContainer').append(toast);
                requestAnimationFrame(function () { toast.addClass('pv-toast--visible'); });

                function dismiss() {
                    toast.removeClass('pv-toast--visible');
                    setTimeout(function () { toast.remove(); }, 200);
                }
                toast.find('.pv-toast__close').on('click', dismiss);
                setTimeout(dismiss, 4500);
            }

            function currentFilterParams() {
                return {
                    role: $('#roleFilter').val(),
                    search: $('#playSearchInput').val(),
                    strategies: getCheckedFilterValues('strategies').join(','),
                    preferred_down: getCheckedFilterValues('preferred_down').join(','),
                    yardage_gain: getCheckedFilterValues('yardage_gain').join(','),
                    play_target: getCheckedFilterValues('play_target').join(','),
                };
            }

            // ---- Generic custom dropdown (Role / Package) ----
            $(document).on('click', '.pv-dropdown__toggle', function (e) {
                e.stopPropagation();
                const list = $(this).siblings('.pv-dropdown__list');
                const willOpen = list.attr('hidden') !== undefined;
                $('.pv-dropdown__list').attr('hidden', true);
                if (willOpen) { list.removeAttr('hidden'); }
            });
            $(document).on('click', '.pv-dropdown__option', function () {
                const dropdown = $(this).closest('.pv-dropdown');
                const value = $(this).data('value');
                dropdown.find('.pv-dropdown__value').text($(this).text());
                dropdown.find('.pv-dropdown__option').removeClass('pv-dropdown__option--active');
                $(this).addClass('pv-dropdown__option--active');
                dropdown.find('input[type="hidden"]').val(value).trigger('change');
                dropdown.find('.pv-dropdown__list').attr('hidden', true);
            });
            $(document).on('click', function () {
                $('.pv-dropdown__list').attr('hidden', true);
            });

            // ---- DataTables ----
            function renderPossession(data) {
                if (!data) { return ''; }
                const isOffense = data === 'offensive';
                return '<span class="pv-pill-badge ' + (isOffense ? 'pv-pill-badge--off' : 'pv-pill-badge--def') + '">'
                    + (isOffense ? 'OFF' : 'DEF') + '</span>';
            }
            function renderStrategies(data) {
                const text = data || '';
                const safe = text.replace(/"/g, '&quot;');
                return '<span class="pv-truncate" title="' + safe + '">' + text + '</span>';
            }

            function dragHandleColumn() {
                return {
                    data: null,
                    defaultContent: '<span class="pv-drag-handle">&#8942;&#8942;</span>',
                    orderable: false,
                    searchable: false,
                };
            }

            const GLOBAL_COLUMNS = [
                dragHandleColumn(),
                {data: 'id', name: 'id'},
                {data: 'play_name', name: 'play_name'},
                {data: 'min_expected_yard', name: 'min_expected_yard'},
                {data: 'preferred_down', name: 'preferred_down'},
                {data: 'possession', name: 'possession', render: renderPossession},
                {data: 'strategies', name: 'strategies', render: renderStrategies},
                {data: 'starter', name: 'starter', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ];
            const LEAGUE_COLUMNS = [
                dragHandleColumn(),
                {data: 'id', name: 'id'},
                {data: 'play_name', name: 'play_name'},
                {data: 'league', name: 'league', orderable: false, searchable: false},
                {data: 'min_expected_yard', name: 'min_expected_yard'},
                {data: 'preferred_down', name: 'preferred_down'},
                {data: 'possession', name: 'possession', render: renderPossession},
                {data: 'strategies', name: 'strategies', render: renderStrategies},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ];

            function buildTable(selector, scope, columns) {
                return $(selector).DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: "{{ route('play.index') }}",
                        data: function (d) {
                            d.scope = scope;
                            d.role = $('#roleFilter').val();
                            d.strategies = getCheckedFilterValues('strategies').join(',');
                            d.preferred_down = getCheckedFilterValues('preferred_down').join(',');
                            d.yardage_gain = getCheckedFilterValues('yardage_gain').join(',');
                            d.play_target = getCheckedFilterValues('play_target').join(',');
                        }
                    },
                    columns: columns,
                    order: [[1, 'desc']],
                    scrollX: true,
                    scrollCollapse: true,
                    dom: '<"row mb-2 align-items-center"<"col-6"l>>rt<"pv-table-footer"<"pv-table-footer__info"i><"pv-table-footer__pagination"p>>',
                    createdRow: function (row, data) {
                        $(row).attr('draggable', 'true');
                        $(row).attr('data-play-id', data.id);
                        $(row).attr('data-source-scope', scope);
                    },
                });
            }

            const globalTable = buildTable('.data-table-global', 'global', GLOBAL_COLUMNS);
            const leagueTable = buildTable('.data-table-league', 'league', LEAGUE_COLUMNS);
            const tables = [globalTable, leagueTable];

            // ---- Drag a row from one panel, drop it on the other to copy it there ----
            $(document).on('dragstart', '.data-table-global tr[draggable="true"], .data-table-league tr[draggable="true"]', function (e) {
                const row = $(this);
                // Read the live attributes, not jQuery's .data() cache - DataTables recycles
                // <tr> DOM nodes across redraws, so a cached .data() value can go stale and
                // silently drag the wrong play.
                const payload = JSON.stringify({ id: row.attr('data-play-id'), sourceScope: row.attr('data-source-scope') });
                e.originalEvent.dataTransfer.setData('text/plain', payload);
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                row.addClass('pv-row-dragging');
            });
            $(document).on('dragend', '.data-table-global tr, .data-table-league tr', function () {
                $(this).removeClass('pv-row-dragging');
            });

            function setupDropZone(panelId, targetScope) {
                const el = document.getElementById(panelId);
                if (!el) { return; }
                el.classList.add('pv-panel--drop-target');
                el.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    el.classList.add('pv-panel--drop-active');
                });
                el.addEventListener('dragleave', function (e) {
                    if (!el.contains(e.relatedTarget)) {
                        el.classList.remove('pv-panel--drop-active');
                    }
                });
                el.addEventListener('drop', function (e) {
                    e.preventDefault();
                    el.classList.remove('pv-panel--drop-active');
                    let payload;
                    try {
                        payload = JSON.parse(e.dataTransfer.getData('text/plain'));
                    } catch (err) {
                        return;
                    }
                    if (!payload || !payload.id || payload.sourceScope === targetScope) {
                        return;
                    }
                    openCopyModal(payload.id, targetScope);
                });
            }
            setupDropZone('globalPlaysPanel', 'global');
            setupDropZone('leaguePlaysPanel', 'league');

            let pendingCopy = null;

            function openCopyModal(id, targetScope) {
                pendingCopy = { id: id, targetScope: targetScope };
                if (targetScope === 'global') {
                    $('#copyPlayModalDesc').text('Copy this play into Global Plays? It will be shared across every league.');
                    $('#copyPlayLeagueField').attr('hidden', true);
                } else {
                    $('#copyPlayModalDesc').text('Choose which league this copy belongs to:');
                    $('#copyPlayLeagueField').removeAttr('hidden');
                    $('#copyPlayLeagueSelect').val('');
                }
                $('#copyPlayModal').removeAttr('hidden');
            }

            function closeCopyModal() {
                pendingCopy = null;
                $('#copyPlayModal').attr('hidden', true);
            }

            $('#copyPlayCancelBtn').on('click', closeCopyModal);
            $('#copyPlayModal').on('click', function (e) {
                if (e.target === this) { closeCopyModal(); }
            });

            $('#copyPlayConfirmBtn').on('click', function () {
                if (!pendingCopy) { return; }
                const payload = { target: pendingCopy.targetScope };
                if (pendingCopy.targetScope === 'league') {
                    const leagueId = $('#copyPlayLeagueSelect').val();
                    if (!leagueId) {
                        showToast('Please choose a league.', 'info');
                        return;
                    }
                    payload.league_id = leagueId;
                }
                $.ajax({
                    url: '/play/' + pendingCopy.id + '/copy',
                    method: 'POST',
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: payload,
                    success: function () {
                        closeCopyModal();
                        globalTable.ajax.reload(null, false);
                        leagueTable.ajax.reload(null, false);
                        showToast('Play copied successfully.', 'success');
                    },
                    error: function (xhr) {
                        showToast(errorMessage(xhr, 'Could not copy play.'), 'error');
                    }
                });
            });

            function redrawAll() {
                const term = $('#playSearchInput').val();
                tables.forEach(function (t) { t.search(term).draw(); });
                updateStarterScopeCount();
            }

            $('#playSearchInput').on('keyup', debounce(redrawAll, 300));
            $('#roleFilter').on('change', redrawAll);

            // ---- Filters popover ----
            const strategyLabels = { 'regular': 'Regular', 'red zone': 'Red Zone', 'hurry up': 'Hurry Up', 'aggressive': 'Aggressive', 'chew clock': 'Chew Clock' };
            const downLabels = { '1': '1st Down', '2': '2nd Down', '3': '3rd Down', '4': '4th Down' };
            const yardageLabels = { 'short': 'Short (1-4)', 'medium': 'Medium (4-7)', 'long': 'Long (7+)', 'open_down': 'Open Down' };
            const targetLabels = { 'offensive': 'Offensive', 'defensive': 'Defensive' };

            function renderPlayFiltersChips() {
                const chips = [];
                getCheckedFilterValues('strategies').forEach(function (v) {
                    chips.push({ group: 'strategies', value: v, label: strategyLabels[v] || v });
                });
                getCheckedFilterValues('preferred_down').forEach(function (v) {
                    chips.push({ group: 'preferred_down', value: v, label: downLabels[v] || v });
                });
                getCheckedFilterValues('yardage_gain').forEach(function (v) {
                    chips.push({ group: 'yardage_gain', value: v, label: yardageLabels[v] || v });
                });
                getCheckedFilterValues('play_target').forEach(function (v) {
                    chips.push({ group: 'play_target', value: v, label: targetLabels[v] || v });
                });

                let html = chips.map(function (chip, i) {
                    return '<span class="pv-chip">' + chip.label
                        + '<a href="#" class="pv-chip__remove" data-chip-index="' + i + '">&times;</a></span>';
                }).join('');
                if (chips.length) {
                    html += '<a href="#" id="clearAllChipsLink" class="pv-chips__clear-all">Clear all</a>';
                }
                $('#playFiltersChips').data('chips', chips).html(html);
            }

            function updatePlayFiltersBadge() {
                const count = getCheckedFilterValues('strategies').length
                    + getCheckedFilterValues('preferred_down').length
                    + getCheckedFilterValues('yardage_gain').length
                    + getCheckedFilterValues('play_target').length;
                if (count > 0) {
                    $('#playFiltersBadge').text(count).show();
                } else {
                    $('#playFiltersBadge').hide();
                }
                renderPlayFiltersChips();
            }

            $('#playFiltersToggle').on('click', function (e) {
                e.stopPropagation();
                $('#playFiltersPanel').attr('hidden', function (i, hidden) { return hidden === undefined; });
            });
            // Close the popover only for clicks genuinely outside it - stopPropagation() here would
            // also block every document-delegated handler for controls nested inside the popover
            // (that's what silently broke the Yardage Gain / Play Target filters before).
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.pv-popover').length) {
                    $('#playFiltersPanel').attr('hidden', true);
                }
            });

            $(document).on('change', '.play-filter-checkbox', function () {
                $(this).closest('.pv-chip-toggle').toggleClass('pv-chip-toggle--active', this.checked);
                updatePlayFiltersBadge();
                redrawAll();
            });

            $(document).on('click', '.play-filter-select-all', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', true)
                    .closest('.pv-chip-toggle').addClass('pv-chip-toggle--active');
                updatePlayFiltersBadge();
                redrawAll();
            });

            $(document).on('click', '.play-filter-clear-group', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', false)
                    .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                updatePlayFiltersBadge();
                redrawAll();
            });

            function clearAllPlayFilters() {
                $('.play-filter-checkbox').prop('checked', false)
                    .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                updatePlayFiltersBadge();
                redrawAll();
            }

            $('#clearAllPlayFiltersBtn').on('click', clearAllPlayFilters);
            $(document).on('click', '#clearAllChipsLink', function (e) {
                e.preventDefault();
                clearAllPlayFilters();
            });

            $(document).on('click', '.pv-chip__remove', function (e) {
                e.preventDefault();
                const index = $(this).data('chip-index');
                const chips = $('#playFiltersChips').data('chips') || [];
                const chip = chips[index];
                if (!chip) { return; }
                $('.play-filter-checkbox[data-group="' + chip.group + '"][value="' + chip.value + '"]')
                    .prop('checked', false)
                    .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                updatePlayFiltersBadge();
                redrawAll();
            });

            updatePlayFiltersBadge();

            // ---- Per-row Starter switch ----
            $(document).on('change', '.starter-toggle', function () {
                const checkbox = $(this);
                const id = checkbox.attr('data-id');
                $.ajax({
                    url: '/play/' + id + '/toggle-starter',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    error: function (xhr) {
                        checkbox.prop('checked', !checkbox.prop('checked')); // revert on failure
                        showToast(errorMessage(xhr, 'Could not update starter status.'), 'error');
                    }
                });
            });

            // ---- Bulk Starter bar: scope + Mark/Remove ----
            let starterScope = 'page';

            $(document).on('click', '.pv-seg__option', function () {
                $(this).siblings().removeClass('pv-seg__option--active');
                $(this).addClass('pv-seg__option--active');
                starterScope = $(this).data('scope');
                updateStarterScopeCount();
            });

            function updateStarterScopeCount() {
                if (starterScope === 'page') {
                    const n = $('.data-table-global .starter-toggle').length;
                    $('#starterScopeCount').text(n + (n === 1 ? ' play on this page' : ' plays on this page'));
                    return;
                }
                $('#starterScopeCount').text('Counting matching plays…');
                $.ajax({
                    url: '{{ route('play.global-ids') }}',
                    method: 'GET',
                    data: currentFilterParams(),
                    success: function (resp) {
                        const n = (resp.ids || []).length;
                        $('#starterScopeCount').text(n + (n === 1 ? ' matching play' : ' matching plays'));
                    }
                });
            }
            globalTable.on('draw', updateStarterScopeCount);
            updateStarterScopeCount();

            function applyStarter(ids, value) {
                if (!ids.length) {
                    showToast('No plays to update.', 'info');
                    return;
                }
                $.ajax({
                    url: '{{ route('play.bulk-toggle-starter') }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { ids: ids, starter: value ? 1 : 0 },
                    success: function () {
                        globalTable.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        showToast(errorMessage(xhr, 'Could not update starter status.'), 'error');
                    }
                });
            }

            function runStarterBulkAction(value) {
                if (starterScope === 'page') {
                    const ids = $('.data-table-global .starter-toggle').map(function () { return $(this).attr('data-id'); }).get();
                    applyStarter(ids, value);
                    return;
                }
                $.ajax({
                    url: '{{ route('play.global-ids') }}',
                    method: 'GET',
                    data: currentFilterParams(),
                    success: function (resp) {
                        applyStarter(resp.ids || [], value);
                    }
                });
            }

            $('#markStarterBtn').on('click', function () { runStarterBulkAction(true); });
            $('#removeStarterBtn').on('click', function () { runStarterBulkAction(false); });
        });
    </script>
@endsection
