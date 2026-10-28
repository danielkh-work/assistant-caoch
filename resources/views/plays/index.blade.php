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
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                <a href="{{ route('play.create') }}" class="btn btn-success">Uploads Play</a>
                <br>
                </div>
            </div>
            <br>
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card card-body py-2 px-3">
                        <div class="d-flex flex-row align-items-center flex-wrap justify-content-end" style="gap: 16px;">
                            <button type="button" id="playFiltersToggle" class="btn btn-outline-secondary btn-sm mb-0">
                                Filters <span id="playFiltersBadge" class="badge badge-primary" style="display:none;"></span>
                            </button>
                        </div>

                        <div id="playFiltersPanel" class="play-filters-panel" style="display:none;">
                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Strategies</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="strategies">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="strategies">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="regular"><span>Regular</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="red zone"><span>Red Zone</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="hurry up"><span>Hurry Up</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="aggressive"><span>Aggressive</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="chew clock"><span>Chew Clock</span></label>
                                </div>
                            </div>

                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Preferred Down</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="preferred_down">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="preferred_down">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="1"><span>1st</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="2"><span>2nd</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="3"><span>3rd</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="4"><span>4th</span></label>
                                </div>
                            </div>

                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Yardage Gain</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="yardage_gain">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="yardage_gain">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="short"><span>Short (1-4)</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="medium"><span>Medium (4-7)</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="long"><span>Long (7+)</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="yardage_gain" value="open_down"><span>Open Down</span></label>
                                </div>
                            </div>

                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Play Target</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="play_target">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="play_target">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="play_target" value="offensive"><span>Offensive</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="play_target" value="defensive"><span>Defensive</span></label>
                                </div>
                            </div>

                            <div class="play-filter-footer">
                                <button type="button" id="clearAllPlayFiltersBtn" class="play-filter-clear-all">Clear all filters</button>
                            </div>
                        </div>
                        <div id="playFiltersChips" class="play-filters-chips"></div>
                    </div>
                </div>
            </div>

            <style>
                .play-filters-panel {
                    margin-top: 14px;
                    padding-top: 16px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: stretch;
                    gap: 16px;
                }
                .play-filter-group {
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 8px;
                    padding: 10px 14px 12px;
                    min-width: 180px;
                }
                .play-filter-group__header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    margin-bottom: 8px;
                }
                .play-filter-group__title {
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    color: #6c757d;
                    white-space: nowrap;
                }
                .play-filter-group__links {
                    font-size: 11.5px;
                    white-space: nowrap;
                }
                .play-filter-group__links a {
                    color: #0d6efd;
                    text-decoration: none;
                    font-weight: 600;
                }
                .play-filter-group__links a:hover { text-decoration: underline; }
                .play-filter-group__sep { margin: 0 4px; color: #ced4da; }

                .play-filter-pills {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                .play-filter-pill {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 13px;
                    color: #495057;
                    cursor: pointer;
                    margin: 0;
                    user-select: none;
                }
                .play-filter-pill input {
                    width: 15px;
                    height: 15px;
                    margin: 0;
                    cursor: pointer;
                    accent-color: #0d6efd;
                }

                .play-filter-footer {
                    display: flex;
                    align-items: center;
                }
                .play-filter-clear-all {
                    background: none;
                    border: 1px solid #dc3545;
                    color: #dc3545;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 7px 14px;
                    cursor: pointer;
                    transition: background 0.15s, color 0.15s;
                    white-space: nowrap;
                }
                .play-filter-clear-all:hover { background: #dc3545; color: #ffffff; }

                .play-filters-chips {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 8px;
                    margin-top: 10px;
                }
                .play-filters-chips:empty { margin-top: 0; }
                .play-filter-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #e7f1ff;
                    border: 1px solid #b6d4fe;
                    color: #0d6efd;
                    border-radius: 999px;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 4px 8px 4px 12px;
                }
                .play-filter-chip__remove {
                    color: #0d6efd;
                    text-decoration: none;
                    font-weight: 700;
                    line-height: 1;
                    padding: 0 2px;
                }
                .play-filter-chip__remove:hover { color: #dc3545; }
                .play-filter-chips__clear-all {
                    color: #dc3545;
                    font-size: 12px;
                    font-weight: 600;
                    text-decoration: underline;
                    margin-left: 2px;
                }

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
                    border: 1px solid #e9ecef;
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
            </style>
            <!-- Small boxes (Stat box) -->
            <div class="row">
                 <div class="table-responsive">
                    <table class="table table-bordered data-table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th width="56px">Picture</th>
                            <th>Play Name</th>
                            <th>Roles</th>
                            <th>League</th>
                            <th>Min Expected Yard</th>
                            <th>preferred_down</th>
                            <th>possession</th>
                            <th>description</th>
                            <th>strategies</th>
                            <th width="100px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                 </div>
            </div>
            <!-- /.row -->
            <!-- Main row -->
            <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->

        <div id="pvImageLightbox" class="pv-lightbox-backdrop" hidden>
            <button type="button" class="pv-lightbox__close">&times;</button>
            <button type="button" class="pv-lightbox__nav pv-lightbox__prev">&#8249;</button>
            <img id="pvLightboxImage" class="pv-lightbox__image" src="" alt="">
            <button type="button" class="pv-lightbox__nav pv-lightbox__next">&#8250;</button>
            <div class="pv-lightbox__dots"></div>
        </div>
    </section>

@endsection

@section('script')
    <script type="text/javascript">
        $(function () {

            function getCheckedFilterValues(group) {
                return $('.play-filter-checkbox[data-group="' + group + '"]:checked')
                    .map(function () { return $(this).val(); })
                    .get();
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
            $('.pv-lightbox__close').on('click', closeLightbox);
            $('.pv-lightbox__prev').on('click', showPrevImage);
            $('.pv-lightbox__next').on('click', showNextImage);
            $('#pvImageLightbox').on('click', function (e) {
                if (e.target === this) { closeLightbox(); }
            });
            $(document).on('keydown', function (e) {
                if ($('#pvImageLightbox').attr('hidden') !== undefined) { return; }
                if (e.key === 'Escape') { closeLightbox(); }
                if (e.key === 'ArrowLeft') { showPrevImage(); }
                if (e.key === 'ArrowRight') { showNextImage(); }
            });

            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('play.index') }}",
                    data: function (d) {
                        d.role = $('#roleFilter').val();
                        d.strategies = getCheckedFilterValues('strategies').join(',');
                        d.preferred_down = getCheckedFilterValues('preferred_down').join(',');
                        d.yardage_gain = getCheckedFilterValues('yardage_gain').join(',');
                        d.play_target = getCheckedFilterValues('play_target').join(',');
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: null, name: 'hmark_center', orderable: false, searchable: false, render: renderPictureStack},
                    {data: 'play_name', name: 'play_name'},
                    {data: 'roles', name: 'roles'},
                    {data: 'league', name: 'league', orderable: false, searchable: false},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession'},
                    {data: 'description', name: 'description'},
                    {data: 'strategies', name: 'strategies'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [0, 'desc'],
                dom: '<"row mb-2 align-items-center"<"col-md-4"l><"col-md-4 text-center"<"#custom-filter-wrapper">><"col-md-4 text-right"f>>rt<"row mt-2"<"col-md-6"i><"col-md-6"p>>',
            });

              $('#custom-filter-wrapper').html(`
                <label class="mb-0">Roles:
                    <select id="roleFilter" class="form-control form-control-sm d-inline-block ml-2" style="width: auto;">
                        <option value="">All</option>
                        <option value="1">Classic basic</option>
                        <option value="2">Classic advance</option>
                        <option value="3">HD HUMAN DASHBOARD</option>
                        <option value="4">Pro basic</option>
                        <option value="5">Pro advance</option>
                    </select>
                </label>
            `);
            $('#roleFilter').on('change', function () {

                table.draw();
            });

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
                    return '<span class="play-filter-chip">' + chip.label
                        + '<a href="#" class="play-filter-chip__remove" data-chip-index="' + i + '">&times;</a></span>';
                }).join('');
                if (chips.length) {
                    html += '<a href="#" id="clearAllChipsLink" class="play-filter-chips__clear-all">Clear all</a>';
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

            $('#playFiltersToggle').on('click', function () {
                $('#playFiltersPanel').slideToggle(150);
            });

            $(document).on('change', '.play-filter-checkbox', function () {
                updatePlayFiltersBadge();
                table.draw();
            });

            $(document).on('click', '.play-filter-select-all', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', true);
                updatePlayFiltersBadge();
                table.draw();
            });

            $(document).on('click', '.play-filter-clear-group', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', false);
                updatePlayFiltersBadge();
                table.draw();
            });

            function clearAllPlayFilters() {
                $('.play-filter-checkbox').prop('checked', false);
                updatePlayFiltersBadge();
                table.draw();
            }

            $('#clearAllPlayFiltersBtn').on('click', clearAllPlayFilters);
            $(document).on('click', '#clearAllChipsLink', function (e) {
                e.preventDefault();
                clearAllPlayFilters();
            });

            $(document).on('click', '.play-filter-chip__remove', function (e) {
                e.preventDefault();
                const index = $(this).data('chip-index');
                const chips = $('#playFiltersChips').data('chips') || [];
                const chip = chips[index];
                if (!chip) { return; }
                $('.play-filter-checkbox[data-group="' + chip.group + '"][value="' + chip.value + '"]').prop('checked', false);
                updatePlayFiltersBadge();
                table.draw();
            });

            updatePlayFiltersBadge();
        });
    </script>
@endsection
