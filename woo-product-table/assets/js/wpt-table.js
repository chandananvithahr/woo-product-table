/**
 * WooCommerce Product Table — front-end script.
 *
 * Responsibilities:
 *  - Client-side search (filter visible rows by name)
 *  - Category dropdown filter (re-query server or filter by data attribute)
 *  - In-stock-only checkbox filter
 *  - Column header click → server-side re-sort via AJAX
 *  - AJAX add-to-cart with quantity
 *  - Pro: CSV export trigger
 *  - Accessible keyboard navigation for sortable headers
 *
 * Depends on: jQuery (WP bundled), wptData (wp_localize_script)
 */

/* global wptData, jQuery */
( function ( $ ) {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────────────

    const state = {
        search:   '',
        category: '',
        instock:  false,
    };

    // ── DOM references ────────────────────────────────────────────────────────────

    const $wrapper     = () => $( '.wpt-wrapper' );
    const $tbody       = () => $( '#wpt-tbody' );
    const $search      = () => $( '#wpt-search' );
    const $category    = () => $( '#wpt-category' );
    const $instock     = () => $( '#wpt-instock' );
    const $clearBtn    = () => $( '#wpt-clear-filters' );
    const $messages    = () => $( '.wpt-messages' );

    // ── Init ──────────────────────────────────────────────────────────────────────

    function init() {
        bindSearch();
        bindCategoryFilter();
        bindInstockFilter();
        bindClearFilters();
        bindSortHeaders();
        bindAddToCart();
        bindCsvExport();
        bindKeyboardSort();
    }

    // ── Search (debounced, client-side) ───────────────────────────────────────────

    function bindSearch() {
        let timer;
        $( document ).on( 'input', '#wpt-search', function () {
            clearTimeout( timer );
            const val = $( this ).val();
            timer = setTimeout( function () {
                state.search = val.trim().toLowerCase();
                applyFilters();
            }, 250 );
        } );
    }

    // ── Category filter ───────────────────────────────────────────────────────────

    function bindCategoryFilter() {
        $( document ).on( 'change', '#wpt-category', function () {
            state.category = $( this ).val().trim().toLowerCase();
            applyFilters();
        } );
    }

    // ── In-stock checkbox ─────────────────────────────────────────────────────────

    function bindInstockFilter() {
        $( document ).on( 'change', '#wpt-instock', function () {
            state.instock = $( this ).is( ':checked' );
            applyFilters();
        } );
    }

    // ── Clear filters ─────────────────────────────────────────────────────────────

    function bindClearFilters() {
        $( document ).on( 'click', '#wpt-clear-filters', function () {
            state.search   = '';
            state.category = '';
            state.instock  = false;

            $search().val( '' );
            $category().val( '' );
            $instock().prop( 'checked', false );

            applyFilters();
        } );
    }

    // ── Apply all active filters to visible rows ───────────────────────────────────

    function applyFilters() {
        const $rows = $tbody().find( 'tr.wpt-row' );

        $rows.each( function () {
            const $row         = $( this );
            const productName  = $row.data( 'product-name' ) || '';
            const instock      = $row.data( 'instock' ) === 1 || $row.data( 'instock' ) === '1';

            let visible = true;

            // Name search.
            if ( state.search && productName.indexOf( state.search ) === -1 ) {
                visible = false;
            }

            // Category filter — the row carries category slugs in data-categories attribute
            // (set by PHP when rendering). Fallback: skip if attribute not present.
            if ( state.category ) {
                const cats = ( $row.data( 'categories' ) || '' ).toString().toLowerCase();
                if ( cats.indexOf( state.category ) === -1 ) {
                    visible = false;
                }
            }

            // In-stock filter.
            if ( state.instock && ! instock ) {
                visible = false;
            }

            $row.toggle( visible );
        } );

        showNoResults();
    }

    /**
     * Show a "no results" row if all rows are hidden.
     */
    function showNoResults() {
        const $rows       = $tbody().find( 'tr.wpt-row' );
        const $noResults  = $tbody().find( '.wpt-no-results-filter' );
        const anyVisible  = $rows.filter( ':visible' ).length > 0;

        if ( ! anyVisible ) {
            if ( $noResults.length === 0 ) {
                const colspan = $wrapper().find( 'thead tr th' ).length || 6;
                $tbody().append(
                    '<tr class="wpt-no-results-filter"><td colspan="' + colspan + '" class="wpt-no-products">' +
                    escapeHtml( wptData.noResultsText || 'No products match your filters.' ) +
                    '</td></tr>'
                );
            }
        } else {
            $noResults.remove();
        }
    }

    // ── Column header sorting (server-side re-query) ───────────────────────────────

    function bindSortHeaders() {
        $( document ).on( 'click', '.wpt-sortable', function () {
            handleSort( $( this ) );
        } );
    }

    function bindKeyboardSort() {
        $( document ).on( 'keydown', '.wpt-sortable', function ( e ) {
            if ( e.key === 'Enter' || e.key === ' ' ) {
                e.preventDefault();
                handleSort( $( this ) );
            }
        } );
    }

    function handleSort( $th ) {
        const sortCol = $th.data( 'sort' );
        const order   = $th.data( 'order' ); // next order to apply
        const $wrap   = $th.closest( '.wpt-wrapper' );

        // Update wrapper data attributes so a page refresh preserves intent.
        $wrap.data( 'sort', sortCol );
        $wrap.data( 'order', order );

        fetchProducts( $wrap, sortCol, order, 1 );
    }

    // ── AJAX product fetch (sort / paginate) ──────────────────────────────────────

    function fetchProducts( $wrap, sort, order, page ) {
        const perPage  = parseInt( $wrap.data( 'per-page' ), 10 ) || 10;

        showTableLoader( $wrap );

        $.ajax( {
            url:    wptData.ajaxUrl,
            method: 'POST',
            data:   {
                action:     'wpt_fetch_products',
                nonce:      wptData.nonce,
                sort:       sort,
                order:      order,
                per_page:   perPage,
                page:       page || 1,
                category:   $( '#wpt-category' ).val() || '',
                search:     $( '#wpt-search' ).val() || '',
                instock:    $( '#wpt-instock' ).is( ':checked' ) ? 1 : 0,
            },
            success: function ( response ) {
                if ( response.success ) {
                    $( '#wpt-tbody' ).html( response.data.rows_html );
                    updateSortHeaders( $wrap, sort, order );

                    if ( response.data.pagination_html ) {
                        $( '#wpt-pagination' ).html( response.data.pagination_html );
                    }
                } else {
                    showMessage( response.data.message || wptData.errorText, 'error' );
                }
            },
            error: function () {
                showMessage( wptData.errorText, 'error' );
            },
            complete: function () {
                hideTableLoader( $wrap );
            },
        } );
    }

    /**
     * Update aria-sort and icon state on all sortable TH elements.
     */
    function updateSortHeaders( $wrap, activeSort, activeOrder ) {
        $wrap.find( '.wpt-sortable' ).each( function () {
            const $th    = $( this );
            const col    = $th.data( 'sort' );
            const isThis = col === activeSort;

            $th.removeClass( 'wpt-sorted' );
            $th.attr( 'aria-sort', 'none' );
            $th.find( '.wpt-sort-icon' ).html( '⇅' ).removeClass( 'wpt-sort-icon--asc wpt-sort-icon--desc' );

            if ( isThis ) {
                const nextOrder = activeOrder === 'ASC' ? 'DESC' : 'ASC';
                $th.addClass( 'wpt-sorted' );
                $th.attr( 'aria-sort', activeOrder === 'ASC' ? 'ascending' : 'descending' );
                $th.data( 'order', nextOrder );
                $th.find( '.wpt-sort-icon' )
                    .html( activeOrder === 'ASC' ? '↑' : '↓' )
                    .addClass( activeOrder === 'ASC' ? 'wpt-sort-icon--asc' : 'wpt-sort-icon--desc' );
            }
        } );
    }

    // ── AJAX Add to Cart ──────────────────────────────────────────────────────────

    function bindAddToCart() {
        $( document ).on( 'click', '.wpt-atc-btn', function () {
            const $btn       = $( this );
            const productId  = parseInt( $btn.data( 'product-id' ), 10 );
            const $row       = $btn.closest( 'tr' );
            const $qtyInput  = $row.find( '.wpt-qty[data-product-id="' + productId + '"]' );
            const qty        = $qtyInput.length ? parseInt( $qtyInput.val(), 10 ) || 1 : 1;

            if ( $btn.hasClass( 'wpt-loading' ) ) {
                return; // prevent double-click
            }

            $btn.addClass( 'wpt-loading' )
                .prop( 'disabled', true )
                .text( wptData.addingText );

            $.ajax( {
                url:    wptData.ajaxUrl,
                method: 'POST',
                data:   {
                    action:     'wpt_add_to_cart',
                    nonce:      wptData.nonce,
                    product_id: productId,
                    quantity:   qty,
                },
                success: function ( response ) {
                    if ( response.success ) {
                        $btn.text( wptData.addedText ).addClass( 'wpt-added' );

                        // Update WooCommerce cart fragments (mini-cart count etc.).
                        $( document.body ).trigger( 'wc_fragment_refresh' );
                        $( document.body ).trigger( 'added_to_cart', [
                            response.data,
                            productId,
                        ] );

                        showMessage( response.data.message, 'success' );

                        // Reset button after 2 s.
                        setTimeout( function () {
                            $btn.removeClass( 'wpt-loading wpt-added' )
                                .prop( 'disabled', false )
                                .text( wptData.addToCartText );
                        }, 2000 );
                    } else {
                        $btn.text( wptData.addToCartText );
                        showMessage( response.data.message || wptData.errorText, 'error' );
                    }
                },
                error: function () {
                    $btn.text( wptData.addToCartText );
                    showMessage( wptData.errorText, 'error' );
                },
                complete: function () {
                    $btn.removeClass( 'wpt-loading' ).prop( 'disabled', false );
                },
            } );
        } );
    }

    // ── CSV Export (Pro) ──────────────────────────────────────────────────────────

    function bindCsvExport() {
        $( document ).on( 'click', '#wpt-export-csv', function () {
            if ( ! wptData.isPro ) {
                showMessage( 'CSV export is a Pro feature.', 'error' );
                return;
            }
            // Trigger download via hidden form.
            const form = $( '<form>', {
                method: 'POST',
                action: wptData.ajaxUrl,
            } );
            form.append( $( '<input>', { type: 'hidden', name: 'action', value: 'wpt_export_csv' } ) );
            form.append( $( '<input>', { type: 'hidden', name: 'nonce',  value: wptData.nonce } ) );
            $( 'body' ).append( form );
            form.submit();
            form.remove();
        } );
    }

    // ── Loader helpers ────────────────────────────────────────────────────────────

    function showTableLoader( $wrap ) {
        $wrap.find( '.wpt-table-wrap' ).addClass( 'wpt-loading' );
        if ( $wrap.find( '.wpt-loader' ).length === 0 ) {
            $wrap.find( '.wpt-table-wrap' ).prepend( '<div class="wpt-loader" aria-live="polite" aria-label="Loading products"></div>' );
        }
    }

    function hideTableLoader( $wrap ) {
        $wrap.find( '.wpt-table-wrap' ).removeClass( 'wpt-loading' );
        $wrap.find( '.wpt-loader' ).remove();
    }

    // ── Message display ───────────────────────────────────────────────────────────

    function showMessage( text, type ) {
        const $msg = $messages();
        $msg.attr( 'class', 'wpt-messages wpt-messages--' + type )
            .text( text )
            .stop( true, true )
            .fadeIn( 200 );

        clearTimeout( $msg.data( 'timer' ) );
        $msg.data( 'timer', setTimeout( function () {
            $msg.fadeOut( 400 );
        }, 4000 ) );
    }

    // ── Utility ───────────────────────────────────────────────────────────────────

    function escapeHtml( str ) {
        return String( str )
            .replace( /&/g,  '&amp;' )
            .replace( /</g,  '&lt;' )
            .replace( />/g,  '&gt;' )
            .replace( /"/g,  '&quot;' )
            .replace( /'/g,  '&#039;' );
    }

    // ── Boot on DOM ready ─────────────────────────────────────────────────────────

    $( function () {
        init();
    } );

} )( jQuery );
