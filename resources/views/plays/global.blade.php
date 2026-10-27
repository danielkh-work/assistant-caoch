@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Global Plays</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Global Plays</li>
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
                <div class="col-md-12 d-flex flex-wrap" style="gap: 10px;">
                    <a href="{{ route('play.create') }}" class="pv-btn pv-btn--primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Upload Play
                    </a>
                    <button type="button" id="manageStarterToggleBtn" class="pv-btn pv-btn--outline">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Manage Starter Plays
                    </button>
                </div>
            </div>

            <!-- ================= Default single-table view ================= -->
            <div id="globalDefaultView">
                <div class="row mb-3">
                    <div class="col-md-12">
                        @include('plays.partials.search-filter-toolbar')
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="pv-panel">
                            <table class="table table-sm table-hover pv-table data-table-global">
                                <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Play Name</th>
                                    <th width="56px">Picture</th>
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
                </div>
            </div>

            <!-- ================= Starter manager (split, drag-and-drop) view ================= -->
            <div id="starterManagerView" hidden>
                <p class="pv-helper-text">Drag a play from <strong>Global Plays</strong> onto <strong>Starter Plays</strong> to mark it as a starter &mdash; or drag it back to remove it. Nothing is saved until you click <strong>Save Changes</strong>.</p>
                <div id="starterPendingBar" class="pv-pending-bar" hidden>
                    <span class="pv-pending-count">0 pending changes</span>
                    <button type="button" id="discardPendingBtn" class="pv-btn pv-btn--outline">Discard</button>
                    <button type="button" id="savePendingBtn" class="pv-btn pv-btn--primary">Save Changes</button>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="pv-panel" id="globalPoolPanel" data-panel="pool">
                            <div class="pv-panel__header">
                                <h3 class="pv-panel__title">Global Plays</h3>
                            </div>
                            @include('plays.partials.search-filter-toolbar')
                            @include('plays.partials.bulk-select-bar', ['label' => 'Add to Starter Plays'])
                            <table class="table table-sm table-hover pv-table data-table-pool">
                                <thead>
                                <tr>
                                    <th width="24px"></th>
                                    <th width="24px"></th>
                                    <th>No</th>
                                    <th>Play Name</th>
                                    <th width="56px">Picture</th>
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
                    <div class="col-lg-6 mb-4">
                        <div class="pv-panel" id="starterPoolPanel" data-panel="starter">
                            <div class="pv-panel__header">
                                <h3 class="pv-panel__title">Starter Plays</h3>
                            </div>
                            @include('plays.partials.search-filter-toolbar')
                            @include('plays.partials.bulk-select-bar', ['label' => 'Remove from Starter'])
                            <table class="table table-sm table-hover pv-table data-table-starter">
                                <thead>
                                <tr>
                                    <th width="24px"></th>
                                    <th width="24px"></th>
                                    <th>No</th>
                                    <th>Play Name</th>
                                    <th width="56px">Picture</th>
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
            </div>

            <div id="pvConfirmModal" class="pv-modal-backdrop" hidden>
                <div class="pv-modal">
                    <h4 class="pv-modal__title">Unsaved changes</h4>
                    <p id="pvConfirmModalMessage" class="pv-modal__desc"></p>
                    <div class="pv-modal__actions">
                        <button type="button" id="pvConfirmCancelBtn" class="pv-btn pv-btn--outline">Cancel</button>
                        <button type="button" id="pvConfirmOkBtn" class="pv-btn pv-btn--primary">Leave without saving</button>
                    </div>
                </div>
            </div>

            <div id="pvImageLightbox" class="pv-lightbox-backdrop" hidden>
                <button type="button" class="pv-lightbox__close">&times;</button>
                <button type="button" class="pv-lightbox__nav pv-lightbox__prev">&#8249;</button>
                <img id="pvLightboxImage" class="pv-lightbox__image" src="" alt="">
                <button type="button" class="pv-lightbox__nav pv-lightbox__next">&#8250;</button>
                <div class="pv-lightbox__dots"></div>
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
        .pv-btn--outline.pv-btn--active { background: var(--pv-accent-soft); border-color: var(--pv-accent); color: var(--pv-accent); }
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

        .pv-helper-text { font-size: 13px; color: var(--pv-text-muted); margin-bottom: 14px; }

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

        /* Generic custom dropdown (Role / Package) */
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
        .pv-filter-group { min-width: 160px; flex: 1 1 160px; }
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

        /* Panel */
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

        .pv-pic-stack {
            position: relative;
            width: 48px;
            height: 40px;
            cursor: pointer;
        }
        .pv-pic-stack__img {
            width: 36px;
            height: 36px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 4px rgba(0,0,0,.25);
            background: #f8f9fa;
            position: absolute;
            top: 1px;
            left: 5px;
            transition: transform .15s ease;
        }
        .pv-pic-stack--1 .pv-pic-stack__img {
            position: static;
            border: 1px solid var(--pv-border);
            box-shadow: none;
        }
        .pv-pic-stack--2 .pv-pic-stack__img--0 { transform: rotate(-10deg) translate(-5px, 1px); z-index: 1; }
        .pv-pic-stack--2 .pv-pic-stack__img--1 { transform: rotate(10deg) translate(5px, 1px); z-index: 2; }
        .pv-pic-stack--3 .pv-pic-stack__img--0 { transform: rotate(-14deg) translate(-8px, 2px); z-index: 1; }
        .pv-pic-stack--3 .pv-pic-stack__img--1 { transform: rotate(0deg) translate(0, -2px); z-index: 3; }
        .pv-pic-stack--3 .pv-pic-stack__img--2 { transform: rotate(14deg) translate(8px, 2px); z-index: 2; }
        .pv-pic-stack:hover .pv-pic-stack__img--0 { transform: rotate(-18deg) translate(-10px, 3px); }
        .pv-pic-stack:hover .pv-pic-stack__img--2 { transform: rotate(18deg) translate(10px, 3px); }

        .pv-lightbox-backdrop {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, .85);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pv-lightbox__image {
            max-width: 85vw;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }
        .pv-lightbox__close {
            position: absolute;
            top: 18px;
            right: 24px;
            background: none;
            border: none;
            color: #fff;
            font-size: 36px;
            line-height: 1;
            cursor: pointer;
        }
        .pv-lightbox__nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, .12);
            border: none;
            color: #fff;
            font-size: 32px;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pv-lightbox__nav:hover { background: rgba(255, 255, 255, .25); }
        .pv-lightbox__prev { left: 18px; }
        .pv-lightbox__next { right: 18px; }
        .pv-lightbox__dots {
            position: absolute;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }
        .pv-lightbox__dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .4);
            cursor: pointer;
        }
        .pv-lightbox__dot--active { background: #ffffff; }

        /* Bulk selection bar (checkbox-driven move to/from Starter Plays) */
        .pv-select-bar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #F9FAFB;
            border: 1px solid var(--pv-border);
            border-radius: 10px;
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 12.5px;
        }
        .pv-select-bar__all { display: flex; align-items: center; gap: 6px; color: var(--pv-text-muted); font-weight: 600; cursor: pointer; white-space: nowrap; }
        .pv-select-matching-link { color: var(--pv-accent); text-decoration: none; font-weight: 600; white-space: nowrap; }
        .pv-select-matching-link:hover { text-decoration: underline; }
        .pv-select-count { color: var(--pv-text-muted); margin-left: auto; white-space: nowrap; }
        .pv-select-clear { color: var(--pv-danger); text-decoration: underline; font-weight: 600; white-space: nowrap; }
        .pv-select-action[disabled] { opacity: .5; cursor: not-allowed; }

        /* Pending-changes bar (staged add/remove, not yet saved) */
        .pv-pending-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--pv-accent-soft);
            border: 1px solid var(--pv-accent);
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--pv-accent-dark);
        }
        .pv-pending-bar .pv-btn { margin-left: auto; }
        .pv-pending-bar .pv-btn + .pv-btn { margin-left: 0; }
        tr.pv-row-pending { background: var(--pv-accent-soft) !important; }
        tr.pv-row-pending td { font-style: italic; }

        /* Confirmation modal (replaces native confirm()) */
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
            width: 380px;
            max-width: calc(100vw - 32px);
            background: #ffffff;
            border-radius: var(--pv-radius);
            padding: 20px;
            box-shadow: 0 24px 48px rgba(17, 24, 39, 0.25);
        }
        .pv-modal__title { font-size: 16px; font-weight: 700; margin: 0 0 6px; color: var(--pv-text); }
        .pv-modal__desc { font-size: 13px; color: var(--pv-text-muted); margin: 0 0 16px; }
        .pv-modal__actions { display: flex; justify-content: flex-end; gap: 8px; }

        /* Starter switch (per row, default view only) */
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

        .pv-truncate {
            display: inline-block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }

        .pv-table-footer { margin-top: 10px; }
        .pv-table-footer__info { font-size: 12.5px; color: var(--pv-text-muted); margin-bottom: 8px; }
        .pv-table-footer__pagination .pagination { flex-wrap: wrap; row-gap: 6px; margin-bottom: 0; }

        .dataTables_scrollBody { border-bottom: 1px solid var(--pv-border); }
        .dataTables_scrollBody::-webkit-scrollbar { height: 8px; }
        .dataTables_scrollBody::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
        .dataTables_scrollBody::-webkit-scrollbar-track { background: transparent; }

        /* Drag-and-drop between Global Plays and Starter Plays */
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

            /**
             * Styled replacement for window.confirm() - resolves true/false once the
             * person picks an option, instead of blocking on an ugly native dialog.
             */
            function confirmDialog(message, okLabel) {
                return new Promise(function (resolve) {
                    $('#pvConfirmModalMessage').text(message);
                    $('#pvConfirmOkBtn').text(okLabel || 'OK');
                    $('#pvConfirmModal').removeAttr('hidden');

                    function cleanup(result) {
                        $('#pvConfirmModal').attr('hidden', true);
                        $('#pvConfirmOkBtn, #pvConfirmCancelBtn').off('click.confirmDialog');
                        resolve(result);
                    }
                    $('#pvConfirmOkBtn').on('click.confirmDialog', function () { cleanup(true); });
                    $('#pvConfirmCancelBtn').on('click.confirmDialog', function () { cleanup(false); });
                });
            }

            function renderPossession(data) {
                return data === 'offensive' ? 'Offensive' : (data === 'defensive' ? 'Defensive' : '');
            }
            function renderStrategies(data) {
                const text = data || '';
                const safe = text.replace(/"/g, '&quot;');
                return '<span class="pv-truncate" title="' + safe + '">' + text + '</span>';
            }

            // Shared by every stacked thumbnail's onerror= so a missing file just drops
            // out of the fan instead of showing a broken-image icon.
            window.pvThumbMissing = function (img) {
                $(img).remove();
            };

            function renderPictureStack(data, type, row) {
                const paths = [row.hmark_left, row.hmark_center, row.hmark_right].filter(function (p) { return !!p; });
                if (!paths.length) { return '<span class="text-muted">&mdash;</span>'; }
                const urls = paths.map(function (p) { return '{{ url('/') }}/' + p; });
                const imgs = urls.map(function (url, i) {
                    return '<img src="' + url + '" class="pv-pic-stack__img pv-pic-stack__img--' + i + '" alt="" onerror="pvThumbMissing(this)">';
                }).join('');
                return '<div class="pv-pic-stack pv-pic-stack--' + urls.length + '" data-images=\'' + JSON.stringify(urls) + '\'>' + imgs + '</div>';
            }

            // ---- Fullscreen image lightbox (single image, or a slideshow for 2-3) ----
            let lightboxImages = [];
            let lightboxIndex = 0;

            function renderLightbox() {
                $('#pvLightboxImage').attr('src', lightboxImages[lightboxIndex]);
                const multi = lightboxImages.length > 1;
                $('.pv-lightbox__nav').toggle(multi);
                const $dots = $('#pvImageLightbox .pv-lightbox__dots').empty();
                if (multi) {
                    lightboxImages.forEach(function (url, i) {
                        $('<span class="pv-lightbox__dot' + (i === lightboxIndex ? ' pv-lightbox__dot--active' : '') + '"></span>')
                            .on('click', function () { lightboxIndex = i; renderLightbox(); })
                            .appendTo($dots);
                    });
                }
            }

            function openLightbox(images, startIndex) {
                lightboxImages = images;
                lightboxIndex = startIndex || 0;
                renderLightbox();
                $('#pvImageLightbox').removeAttr('hidden');
            }

            function closeLightbox() {
                $('#pvImageLightbox').attr('hidden', true);
            }

            function showPrevImage() {
                lightboxIndex = (lightboxIndex - 1 + lightboxImages.length) % lightboxImages.length;
                renderLightbox();
            }

            function showNextImage() {
                lightboxIndex = (lightboxIndex + 1) % lightboxImages.length;
                renderLightbox();
            }

            $(document).on('click', '.pv-pic-stack', function () {
                const images = JSON.parse($(this).attr('data-images'));
                openLightbox(images, 0);
            });
            $(document).on('click', '.pv-lightbox__close', closeLightbox);
            $(document).on('click', '.pv-lightbox__prev', showPrevImage);
            $(document).on('click', '.pv-lightbox__next', showNextImage);
            $(document).on('click', '#pvImageLightbox', function (e) {
                if (e.target === this) { closeLightbox(); }
            });
            $(document).on('keydown', function (e) {
                if ($('#pvImageLightbox').attr('hidden') !== undefined) { return; }
                if (e.key === 'Escape') { closeLightbox(); }
                if (e.key === 'ArrowLeft') { showPrevImage(); }
                if (e.key === 'ArrowRight') { showNextImage(); }
            });

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

            const strategyLabels = { 'regular': 'Regular', 'red zone': 'Red Zone', 'hurry up': 'Hurry Up', 'aggressive': 'Aggressive', 'chew clock': 'Chew Clock' };
            const downLabels = { '1': '1st Down', '2': '2nd Down', '3': '3rd Down', '4': '4th Down' };
            const yardageLabels = { 'short': 'Short (1-4)', 'medium': 'Medium (4-7)', 'long': 'Long (7+)', 'open_down': 'Open Down' };
            const targetLabels = { 'offensive': 'Offensive', 'defensive': 'Defensive' };
            const FILTER_LABELS = { strategies: strategyLabels, preferred_down: downLabels, yardage_gain: yardageLabels, play_target: targetLabels };

            /**
             * Builds one independent "search + filters + DataTable" unit, scoped entirely to
             * its own root element - so the default Global Plays table and the two split
             * panels (Global pool / Starter) never share filter state with each other.
             */
            function initPlayUnit(root, options) {
                const $root = $(root);

                function getCheckedFilterValues(group) {
                    return $root.find('.play-filter-checkbox[data-group="' + group + '"]:checked')
                        .map(function () { return $(this).val(); })
                        .get();
                }

                function currentFilterData(d) {
                    d = d || {};
                    d.scope = 'global';
                    if (options.extraAjaxData) { $.extend(d, options.extraAjaxData); }
                    d.role = $root.find('.pv-unit-role').val();
                    d.strategies = getCheckedFilterValues('strategies').join(',');
                    d.preferred_down = getCheckedFilterValues('preferred_down').join(',');
                    d.yardage_gain = getCheckedFilterValues('yardage_gain').join(',');
                    d.play_target = getCheckedFilterValues('play_target').join(',');
                    return d;
                }

                const table = $root.find(options.tableSelector).DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    ajax: {
                        url: "{{ route('play.index') }}",
                        data: function (d) { return currentFilterData(d); }
                    },
                    columns: options.columns,
                    order: [[options.orderColumn, 'desc']],
                    scrollX: true,
                    scrollCollapse: true,
                    dom: '<"row mb-2 align-items-center"<"col-6"l>>rt<"pv-table-footer"<"pv-table-footer__info"i><"pv-table-footer__pagination"p>>',
                    createdRow: options.createdRow || undefined,
                });

                function redrawAll() {
                    const term = $root.find('.pv-unit-search').val();
                    table.search(term || '').draw();
                }

                $root.find('.pv-unit-search').on('keyup', debounce(redrawAll, 300));
                $root.find('.pv-unit-role').on('change', redrawAll);

                function renderChips() {
                    const chips = [];
                    ['strategies', 'preferred_down', 'yardage_gain', 'play_target'].forEach(function (group) {
                        getCheckedFilterValues(group).forEach(function (v) {
                            chips.push({ group: group, value: v, label: (FILTER_LABELS[group] && FILTER_LABELS[group][v]) || v });
                        });
                    });
                    let html = chips.map(function (chip, i) {
                        return '<span class="pv-chip">' + chip.label
                            + '<a href="#" class="pv-chip__remove" data-chip-index="' + i + '">&times;</a></span>';
                    }).join('');
                    if (chips.length) {
                        html += '<a href="#" class="pv-chips__clear-all pv-unit-clear-all-link">Clear all</a>';
                    }
                    $root.find('.pv-unit-chips').data('chips', chips).html(html);
                }

                function updateBadge() {
                    const count = ['strategies', 'preferred_down', 'yardage_gain', 'play_target']
                        .reduce(function (sum, group) { return sum + getCheckedFilterValues(group).length; }, 0);
                    const $badge = $root.find('.pv-unit-badge');
                    if (count > 0) { $badge.text(count).show(); } else { $badge.hide(); }
                    renderChips();
                }

                $root.find('.pv-unit-filters-toggle').on('click', function (e) {
                    e.stopPropagation();
                    $root.find('.pv-unit-filters-panel').attr('hidden', function (i, hidden) { return hidden === undefined; });
                });
                $(document).on('click', function (e) {
                    if (!$(e.target).closest('.pv-popover').length) {
                        $root.find('.pv-unit-filters-panel').attr('hidden', true);
                    }
                });

                $root.on('change', '.play-filter-checkbox', function () {
                    $(this).closest('.pv-chip-toggle').toggleClass('pv-chip-toggle--active', this.checked);
                    updateBadge();
                    redrawAll();
                });
                $root.on('click', '.play-filter-select-all', function (e) {
                    e.preventDefault();
                    const group = $(this).data('group');
                    $root.find('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', true)
                        .closest('.pv-chip-toggle').addClass('pv-chip-toggle--active');
                    updateBadge();
                    redrawAll();
                });
                $root.on('click', '.play-filter-clear-group', function (e) {
                    e.preventDefault();
                    const group = $(this).data('group');
                    $root.find('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', false)
                        .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    redrawAll();
                });

                function clearAllFilters() {
                    $root.find('.play-filter-checkbox').prop('checked', false)
                        .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    redrawAll();
                }
                $root.find('.pv-unit-clear-all-btn').on('click', clearAllFilters);
                $root.on('click', '.pv-unit-clear-all-link', function (e) {
                    e.preventDefault();
                    clearAllFilters();
                });
                $root.on('click', '.pv-chip__remove', function (e) {
                    e.preventDefault();
                    const index = $(this).data('chip-index');
                    const chips = $root.find('.pv-unit-chips').data('chips') || [];
                    const chip = chips[index];
                    if (!chip) { return; }
                    $root.find('.play-filter-checkbox[data-group="' + chip.group + '"][value="' + chip.value + '"]')
                        .prop('checked', false)
                        .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    redrawAll();
                });

                updateBadge();

                return table;
            }

            // ---- Default single-table Global Plays view ----
            const defaultTable = initPlayUnit('#globalDefaultView', {
                tableSelector: '.data-table-global',
                orderColumn: 0,
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: null, name: 'hmark_center', orderable: false, searchable: false, render: renderPictureStack},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession', render: renderPossession},
                    {data: 'strategies', name: 'strategies', render: renderStrategies},
                    {data: 'starter', name: 'starter', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
            });

            $(document).on('change', '#globalDefaultView .starter-toggle', function () {
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

            // ---- Starter manager: Global pool (not yet starter) <-> Starter Plays ----
            // Drag-and-drop and bulk-select only STAGE a change locally (pendingChanges);
            // nothing is persisted until "Save Changes" is clicked.
            let managerInitialized = false;
            let poolApi = null;
            let starterApi = null;
            const pendingChanges = new Map(); // id(string) -> { targetStarter: bool, rowData: object }

            function originIsStarter(id) {
                return (starterApi.getFreshRows() || []).some(function (r) { return String(r.id) === id; });
            }

            function findRowData(id) {
                if (pendingChanges.has(id)) { return pendingChanges.get(id).rowData; }
                const fromPool = (poolApi.getFreshRows() || []).find(function (r) { return String(r.id) === id; });
                if (fromPool) { return fromPool; }
                return (starterApi.getFreshRows() || []).find(function (r) { return String(r.id) === id; });
            }

            function stagePlay(id, targetStarter, rowData) {
                id = String(id);
                if (targetStarter === originIsStarter(id)) {
                    pendingChanges.delete(id);
                } else {
                    pendingChanges.set(id, { targetStarter: targetStarter, rowData: rowData });
                }
                rerenderBothPanels();
            }

            function visibleRowsFor(freshRows, isStarterBucket) {
                const rows = freshRows.filter(function (r) {
                    const pending = pendingChanges.get(String(r.id));
                    return !pending || pending.targetStarter === isStarterBucket;
                });
                pendingChanges.forEach(function (pending, id) {
                    if (pending.targetStarter === isStarterBucket && !rows.some(function (r) { return String(r.id) === id; })) {
                        rows.push($.extend({}, pending.rowData, { __pending: true }));
                    }
                });
                return rows;
            }

            function updatePendingBar() {
                const n = pendingChanges.size;
                $('#starterPendingBar').attr('hidden', n === 0);
                $('#starterPendingBar .pv-pending-count').text(n + (n === 1 ? ' pending change' : ' pending changes'));
            }

            function rerenderBothPanels() {
                poolApi.table.clear().rows.add(visibleRowsFor(poolApi.getFreshRows(), false)).draw(false);
                starterApi.table.clear().rows.add(visibleRowsFor(starterApi.getFreshRows(), true)).draw(false);
                updatePendingBar();
            }

            function selectColumn() {
                return { data: null, defaultContent: '<input type="checkbox" class="pv-row-select">', orderable: false, searchable: false };
            }
            function dragHandleColumn() {
                return { data: null, defaultContent: '<span class="pv-drag-handle">&#8942;&#8942;</span>', orderable: false, searchable: false };
            }
            function sharedColumns() {
                return [
                    selectColumn(),
                    dragHandleColumn(),
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: null, name: 'hmark_center', orderable: false, searchable: false, render: renderPictureStack},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession', render: renderPossession},
                    {data: 'strategies', name: 'strategies', render: renderStrategies},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ];
            }

            /**
             * A manager panel loads its ENTIRE matching set client-side (length=-1 - small
             * dataset) so staged moves can be overlaid locally without fighting server-side
             * pagination, and so "select all matching" is just reading the already-loaded set.
             */
            function setupManagerPanel(root, scope, starterValue) {
                const $root = $(root);
                let freshRows = [];
                let selectedIds = new Set();

                function getCheckedFilterValues(group) {
                    return $root.find('.play-filter-checkbox[data-group="' + group + '"]:checked')
                        .map(function () { return $(this).val(); })
                        .get();
                }

                const table = $root.find(scope === 'pool' ? '.data-table-pool' : '.data-table-starter').DataTable({
                    data: [],
                    columns: sharedColumns(),
                    order: [[2, 'desc']],
                    scrollX: true,
                    scrollCollapse: true,
                    dom: '<"row mb-2 align-items-center"<"col-6"l>>rt<"pv-table-footer"<"pv-table-footer__info"i><"pv-table-footer__pagination"p>>',
                    createdRow: function (row, data) {
                        $(row).attr('draggable', 'true');
                        $(row).attr('data-play-id', data.id);
                        $(row).attr('data-source-scope', scope);
                        if (data.__pending) { $(row).addClass('pv-row-pending'); }
                    },
                });

                function fetchFresh() {
                    $.ajax({
                        url: "{{ route('play.index') }}",
                        method: 'GET',
                        data: {
                            draw: 1,
                            start: 0,
                            length: -1,
                            scope: 'global',
                            starter: starterValue,
                            role: $root.find('.pv-unit-role').val(),
                            strategies: getCheckedFilterValues('strategies').join(','),
                            preferred_down: getCheckedFilterValues('preferred_down').join(','),
                            yardage_gain: getCheckedFilterValues('yardage_gain').join(','),
                            play_target: getCheckedFilterValues('play_target').join(','),
                        },
                        success: function (json) {
                            freshRows = json.data || [];
                            selectedIds.clear();
                            rerenderBothPanels();
                        }
                    });
                }

                // ---- filters popover (strategies / preferred down / yardage gain / play target) ----
                function renderChips() {
                    const chips = [];
                    ['strategies', 'preferred_down', 'yardage_gain', 'play_target'].forEach(function (group) {
                        getCheckedFilterValues(group).forEach(function (v) {
                            chips.push({ group: group, value: v, label: (FILTER_LABELS[group] && FILTER_LABELS[group][v]) || v });
                        });
                    });
                    let html = chips.map(function (chip, i) {
                        return '<span class="pv-chip">' + chip.label + '<a href="#" class="pv-chip__remove" data-chip-index="' + i + '">&times;</a></span>';
                    }).join('');
                    if (chips.length) { html += '<a href="#" class="pv-chips__clear-all pv-unit-clear-all-link">Clear all</a>'; }
                    $root.find('.pv-unit-chips').data('chips', chips).html(html);
                }
                function updateBadge() {
                    const count = ['strategies', 'preferred_down', 'yardage_gain', 'play_target']
                        .reduce(function (sum, group) { return sum + getCheckedFilterValues(group).length; }, 0);
                    const $badge = $root.find('.pv-unit-badge');
                    if (count > 0) { $badge.text(count).show(); } else { $badge.hide(); }
                    renderChips();
                }
                $root.find('.pv-unit-filters-toggle').on('click', function (e) {
                    e.stopPropagation();
                    $root.find('.pv-unit-filters-panel').attr('hidden', function (i, hidden) { return hidden === undefined; });
                });
                $(document).on('click', function (e) {
                    if (!$(e.target).closest('.pv-popover').length) {
                        $root.find('.pv-unit-filters-panel').attr('hidden', true);
                    }
                });
                $root.on('change', '.play-filter-checkbox', function () {
                    $(this).closest('.pv-chip-toggle').toggleClass('pv-chip-toggle--active', this.checked);
                    updateBadge();
                    fetchFresh();
                });
                $root.on('click', '.play-filter-select-all', function (e) {
                    e.preventDefault();
                    const group = $(this).data('group');
                    $root.find('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', true)
                        .closest('.pv-chip-toggle').addClass('pv-chip-toggle--active');
                    updateBadge();
                    fetchFresh();
                });
                $root.on('click', '.play-filter-clear-group', function (e) {
                    e.preventDefault();
                    const group = $(this).data('group');
                    $root.find('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', false)
                        .closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    fetchFresh();
                });
                function clearAllFilters() {
                    $root.find('.play-filter-checkbox').prop('checked', false).closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    fetchFresh();
                }
                $root.find('.pv-unit-clear-all-btn').on('click', clearAllFilters);
                $root.on('click', '.pv-unit-clear-all-link', function (e) { e.preventDefault(); clearAllFilters(); });
                $root.on('click', '.pv-chip__remove', function (e) {
                    e.preventDefault();
                    const index = $(this).data('chip-index');
                    const chips = $root.find('.pv-unit-chips').data('chips') || [];
                    const chip = chips[index];
                    if (!chip) { return; }
                    $root.find('.play-filter-checkbox[data-group="' + chip.group + '"][value="' + chip.value + '"]')
                        .prop('checked', false).closest('.pv-chip-toggle').removeClass('pv-chip-toggle--active');
                    updateBadge();
                    fetchFresh();
                });
                $root.find('.pv-unit-role').on('change', fetchFresh);
                $root.find('.pv-unit-search').on('keyup', debounce(function () {
                    table.search($root.find('.pv-unit-search').val() || '').draw();
                }, 300));
                updateBadge();

                // ---- bulk selection -> stage only ----
                function pageRows() { return $root.find('tbody tr'); }
                function syncHeaderCheckbox() {
                    const rows = pageRows();
                    const total = rows.length;
                    const checkedCount = rows.filter(function () { return selectedIds.has($(this).attr('data-play-id')); }).length;
                    const $header = $root.find('.pv-select-all-page');
                    $header.prop('checked', total > 0 && checkedCount === total);
                    $header.prop('indeterminate', checkedCount > 0 && checkedCount < total);
                }
                function syncPageCheckboxes() {
                    pageRows().each(function () {
                        $(this).find('.pv-row-select').prop('checked', selectedIds.has($(this).attr('data-play-id')));
                    });
                    syncHeaderCheckbox();
                }
                function updateSelectBar() {
                    const count = selectedIds.size;
                    $root.find('.pv-select-count').text(count + ' selected');
                    $root.find('.pv-select-action').prop('disabled', count === 0);
                    $root.find('.pv-select-clear').toggle(count > 0);
                }
                table.on('draw', function () {
                    syncPageCheckboxes();
                    $root.find('.pv-select-matching-link').text('Select all ' + table.rows({ search: 'applied' }).count() + ' matching');
                    updateSelectBar();
                });
                $root.on('change', '.pv-row-select', function () {
                    const id = $(this).closest('tr').attr('data-play-id');
                    if (this.checked) { selectedIds.add(id); } else { selectedIds.delete(id); }
                    syncHeaderCheckbox();
                    updateSelectBar();
                });
                $root.find('.pv-select-all-page').on('change', function () {
                    const checked = this.checked;
                    pageRows().each(function () {
                        const id = $(this).attr('data-play-id');
                        if (checked) { selectedIds.add(id); } else { selectedIds.delete(id); }
                    });
                    syncPageCheckboxes();
                    updateSelectBar();
                });
                $root.on('click', '.pv-select-matching-link', function (e) {
                    e.preventDefault();
                    selectedIds = new Set(table.rows({ search: 'applied' }).data().toArray().map(function (r) { return String(r.id); }));
                    syncPageCheckboxes();
                    updateSelectBar();
                });
                $root.on('click', '.pv-select-clear', function (e) {
                    e.preventDefault();
                    selectedIds.clear();
                    syncPageCheckboxes();
                    updateSelectBar();
                });
                $root.on('click', '.pv-select-action', function () {
                    if (!selectedIds.size) { return; }
                    const ids = Array.from(selectedIds);
                    ids.forEach(function (id) {
                        const rowData = findRowData(id);
                        if (rowData) { stagePlay(id, scope === 'pool', rowData); }
                    });
                    selectedIds.clear();
                    syncPageCheckboxes();
                    updateSelectBar();
                    showToast(ids.length + (ids.length === 1 ? ' play staged' : ' plays staged') + ' - click Save Changes to apply.', 'info');
                });
                updateSelectBar();

                return {
                    table: table,
                    fetchFresh: fetchFresh,
                    getFreshRows: function () { return freshRows; },
                };
            }

            function initStarterManager() {
                if (managerInitialized) { return; }
                managerInitialized = true;

                poolApi = setupManagerPanel('#globalPoolPanel', 'pool', '0');
                starterApi = setupManagerPanel('#starterPoolPanel', 'starter', '1');
                poolApi.fetchFresh();
                starterApi.fetchFresh();

                $(document).on('dragstart', '.data-table-pool tr[draggable="true"], .data-table-starter tr[draggable="true"]', function (e) {
                    const tr = this;
                    const isPool = $(tr).closest('table').hasClass('data-table-pool');
                    const rowData = (isPool ? poolApi.table : starterApi.table).row(tr).data();
                    const row = $(tr);
                    const payload = { id: row.attr('data-play-id'), sourceScope: row.attr('data-source-scope'), rowData: rowData };
                    e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(payload));
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                    row.addClass('pv-row-dragging');
                });
                $(document).on('dragend', '.data-table-pool tr, .data-table-starter tr', function () {
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
                        if (!el.contains(e.relatedTarget)) { el.classList.remove('pv-panel--drop-active'); }
                    });
                    el.addEventListener('drop', function (e) {
                        e.preventDefault();
                        el.classList.remove('pv-panel--drop-active');
                        let payload;
                        try { payload = JSON.parse(e.dataTransfer.getData('text/plain')); } catch (err) { return; }
                        if (!payload || !payload.id || payload.sourceScope === targetScope) { return; }
                        stagePlay(payload.id, targetScope === 'starter', payload.rowData);
                        showToast(
                            (targetScope === 'starter' ? 'Staged to become a starter play.' : 'Staged for removal from starter plays.')
                                + ' Click Save Changes to apply.',
                            'info'
                        );
                    });
                }
                setupDropZone('globalPoolPanel', 'pool');
                setupDropZone('starterPoolPanel', 'starter');
            }

            $('#savePendingBtn').on('click', function () {
                if (!pendingChanges.size) { return; }
                const toStarterIds = [];
                const toPoolIds = [];
                pendingChanges.forEach(function (change, id) {
                    (change.targetStarter ? toStarterIds : toPoolIds).push(id);
                });
                const requests = [];
                if (toStarterIds.length) {
                    requests.push($.ajax({
                        url: '{{ route('play.bulk-toggle-starter') }}',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        data: { ids: toStarterIds, starter: 1 },
                    }));
                }
                if (toPoolIds.length) {
                    requests.push($.ajax({
                        url: '{{ route('play.bulk-toggle-starter') }}',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        data: { ids: toPoolIds, starter: 0 },
                    }));
                }
                $.when.apply($, requests).done(function () {
                    const n = pendingChanges.size;
                    pendingChanges.clear();
                    showToast(n + (n === 1 ? ' change saved.' : ' changes saved.'), 'success');
                    poolApi.fetchFresh();
                    starterApi.fetchFresh();
                    defaultTable.ajax.reload(null, false);
                }).fail(function (xhr) {
                    showToast(errorMessage(xhr, 'Could not save changes.'), 'error');
                });
            });

            $('#discardPendingBtn').on('click', function () {
                const n = pendingChanges.size;
                pendingChanges.clear();
                rerenderBothPanels();
                showToast(n + (n === 1 ? ' change discarded.' : ' changes discarded.'), 'info');
            });

            function leaveManagerView($btn) {
                pendingChanges.clear();
                $('#starterManagerView').attr('hidden', true);
                $('#globalDefaultView').removeAttr('hidden');
                $btn.removeClass('pv-btn--active');
                defaultTable.ajax.reload(null, false);
            }

            function enterManagerView($btn) {
                $('#globalDefaultView').attr('hidden', true);
                $('#starterManagerView').removeAttr('hidden');
                $btn.addClass('pv-btn--active');
                if (!managerInitialized) {
                    initStarterManager();
                } else {
                    poolApi.fetchFresh();
                    starterApi.fetchFresh();
                }
            }

            $('#manageStarterToggleBtn').on('click', function () {
                const $btn = $(this);
                const showingManager = $('#starterManagerView').attr('hidden') === undefined;
                if (!showingManager) {
                    enterManagerView($btn);
                    return;
                }
                if (!pendingChanges.size) {
                    leaveManagerView($btn);
                    return;
                }
                const n = pendingChanges.size;
                confirmDialog(
                    n + (n === 1 ? ' unsaved change' : ' unsaved changes') + ' will be lost if you leave now.',
                    'Leave without saving'
                ).then(function (confirmed) {
                    if (confirmed) { leaveManagerView($btn); }
                });
            });
        });
    </script>
@endsection
