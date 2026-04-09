/**
 * Payvessel WooCommerce Admin JS
 */
(function($) {
    'use strict';

    var PayvesselAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTransactionsPage();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Refresh transactions
            $(document).on('click', '.payvessel-refresh-transactions', this.loadTransactions);
            
            // Filter by status
            $(document).on('change', '#payvessel-status-filter', this.loadTransactions);

            // Pagination
            $(document).on('click', '.payvessel-pagination a', this.handlePagination);
        },

        /**
         * Initialize transactions page
         */
        initTransactionsPage: function() {
            if ($('#payvessel-transactions-table').length) {
                this.loadTransactions();
            }
        },

        /**
         * Load transactions via AJAX
         */
        loadTransactions: function(e) {
            if (e) e.preventDefault();

            var page = $(this).data('page') || 1;
            var status = $('#payvessel-status-filter').val() || '';
            var $table = $('#payvessel-transactions-table');
            var $tbody = $table.find('tbody');

            $.ajax({
                url: payvessel_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'payvessel_get_transactions',
                    page: page,
                    status: status,
                    nonce: payvessel_admin.nonce
                },
                beforeSend: function() {
                    $tbody.html('<tr><td colspan="7" style="text-align:center;">Loading...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        PayvesselAdmin.renderTransactions(response.data.transactions);
                        PayvesselAdmin.renderPagination(response.data.pages, page);
                    } else {
                        $tbody.html('<tr><td colspan="7" style="text-align:center;">Error loading transactions</td></tr>');
                    }
                },
                error: function() {
                    $tbody.html('<tr><td colspan="7" style="text-align:center;">Error loading transactions</td></tr>');
                }
            });
        },

        /**
         * Render transactions table
         */
        renderTransactions: function(transactions) {
            var $tbody = $('#payvessel-transactions-table tbody');
            $tbody.empty();

            if (transactions.length === 0) {
                $tbody.html('<tr><td colspan="7" style="text-align:center;">No transactions found</td></tr>');
                return;
            }

            transactions.forEach(function(txn) {
                var statusClass = 'status-' + txn.status;
                var row = '<tr>' +
                    '<td><a href="' + adminurl + 'post.php?post=' + txn.id + '&action=edit">#' + txn.id + '</a></td>' +
                    '<td>' + (txn.reference || '-') + '</td>' +
                    '<td>' + txn.currency + ' ' + parseFloat(txn.amount).toLocaleString() + '</td>' +
                    '<td><span class="order-status ' + statusClass + '">' + txn.status + '</span></td>' +
                    '<td>' + (txn.channel || '-') + '</td>' +
                    '<td>' + txn.customer + '</td>' +
                    '<td>' + txn.date + '</td>' +
                    '</tr>';
                $tbody.append(row);
            });
        },

        /**
         * Render pagination
         */
        renderPagination: function(totalPages, currentPage) {
            var $pagination = $('.payvessel-pagination');
            $pagination.empty();

            if (totalPages <= 1) return;

            for (var i = 1; i <= totalPages; i++) {
                var activeClass = i === parseInt(currentPage) ? 'current' : '';
                $pagination.append('<a href="#" class="' + activeClass + '" data-page="' + i + '">' + i + '</a> ');
            }
        },

        /**
         * Handle pagination click
         */
        handlePagination: function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            PayvesselAdmin.loadTransactions.call({data: function(){return page}});
        }
    };

    // Initialize
    $(document).ready(function() {
        PayvesselAdmin.init();
    });

    // Expose for global access
    window.PayvesselAdmin = PayvesselAdmin;

})(jQuery);

// Admin URL for links
var adminurl = typeof ajaxurl !== 'undefined' ? ajaxurl.replace('admin-ajax.php', '') : '/wp-admin/';
