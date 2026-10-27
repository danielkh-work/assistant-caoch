<div class="pv-toolbar">
    <div class="pv-toolbar__row">
        <div class="pv-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" class="pv-search__input pv-unit-search" placeholder="Search play titles&hellip;">
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
            <input type="hidden" class="pv-unit-role" value="">
        </div>

        <div class="pv-popover">
            <button type="button" class="pv-btn pv-btn--outline pv-unit-filters-toggle">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                Filters
                <span class="pv-badge pv-unit-badge" style="display:none;"></span>
            </button>

            <div class="pv-filters-panel pv-unit-filters-panel" hidden>
                @include('plays.partials.filter-groups')
                <div class="pv-filter-footer">
                    <button type="button" class="pv-btn pv-btn--ghost-danger pv-unit-clear-all-btn">Clear all filters</button>
                </div>
            </div>
        </div>
    </div>

    <div class="pv-unit-chips pv-chips"></div>
</div>
