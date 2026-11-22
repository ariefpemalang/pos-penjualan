(function ($) {
    'use strict';

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    window.AthaPurchaseEdit = {
        init: function (options) {
            const settings = $.extend({
                updateUrl: '',
                redirectUrl: '',
                items: [],
                initial: {},
            }, options);

            const cart = (settings.initial.items || []).map(function (line) {
                return {
                    item_id: line.item_id,
                    code: line.code,
                    name: line.name,
                    quantity: parseInt(line.quantity, 10) || 1,
                    unit_price: parseFloat(line.unit_price) || 0,
                    subtotal: Math.round((parseFloat(line.unit_price) || 0) * (parseInt(line.quantity, 10) || 1)),
                };
            });

            const $itemsBody = $('#items-cart-body');
            const $itemsEmpty = $('#items-cart-empty');
            const $submit = $('#btn-submit');

            function findItem(id) {
                return settings.items.find(function (i) { return String(i.id) === String(id); });
            }

            function getCurrentStock(itemId) {
                const item = findItem(itemId);
                return item ? parseInt(item.stock, 10) || 0 : 0;
            }

            function minAllowedQty(line) {
                return Math.max(1, line.quantity - getCurrentStock(line.item_id));
            }

            function recalcLine(line) {
                line.subtotal = Math.round(line.unit_price * line.quantity);
            }

            function renderCart() {
                $itemsBody.empty();
                if (!cart.length) {
                    $itemsEmpty.removeClass('d-none');
                } else {
                    $itemsEmpty.addClass('d-none');
                    cart.forEach(function (line, index) {
                        $itemsBody.append(`
                            <tr>
                                <td>
                                    <div class="fw-medium">${line.code}</div>
                                    <div class="text-muted small">${line.name}</div>
                                </td>
                                <td class="text-center" style="width:100px">
                                    <input type="number" class="form-control form-control-sm form-control-clean cart-qty-input text-center"
                                        data-index="${index}"
                                        value="${line.quantity}" min="1" step="1">
                                </td>
                                <td class="text-end" style="width:160px">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text py-0 px-2 text-muted small">Rp</span>
                                        <input type="number" class="form-control form-control-sm form-control-clean text-end cart-price-input"
                                            data-index="${index}"
                                            value="${line.unit_price}" min="0" step="1">
                                    </div>
                                </td>
                                <td class="text-end fw-medium cart-subtotal">${formatRp(line.subtotal)}</td>
                                <td class="text-end" style="width:50px">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${index}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
                updateSummary();
            }

            function updateSummary() {
                const subtotal = cart.reduce(function (s, l) { return s + l.subtotal; }, 0);
                let discount = parseFloat($('#discount').val()) || 0;
                discount = Math.max(0, Math.min(discount, subtotal));
                const total = subtotal - discount;
                const paymentOk = $('#payment_method').val() !== 'transfer' || !!$('#bank_account_id').val();

                $('#sum-subtotal').text(formatRp(subtotal));
                $('#sum-discount').text('- ' + formatRp(discount));
                $('#sum-total').text(formatRp(total));
                $submit.prop('disabled', cart.length === 0 || !paymentOk);
            }

            $('#discount, #payment_method, #bank_account_id').on('change input', updateSummary);

            let isAdding = false;
            function addItemToCart(item, qty, customPrice) {
                if (!item) return;
                qty = qty && qty > 0 ? qty : 1;

                const parsedPrice = customPrice !== undefined && customPrice !== null && customPrice !== '' && !isNaN(customPrice)
                    ? Math.max(0, parseFloat(customPrice))
                    : (parseFloat(item.purchase_price) || 0);

                const existing = cart.find(function (l) { return String(l.item_id) === String(item.id); });

                if (existing) {
                    existing.quantity += qty;
                    if (customPrice !== undefined && customPrice !== null && customPrice !== '') {
                        existing.unit_price = parsedPrice;
                    }
                    recalcLine(existing);
                } else {
                    cart.push({
                        item_id: item.id,
                        code: item.code,
                        name: item.name,
                        quantity: qty,
                        unit_price: parsedPrice,
                        subtotal: parsedPrice * qty,
                    });
                }

                renderCart();
                $('#item_qty').val('1');
                $('#item_price').val('');
                $('#item-hint').text('');
            }

            function handleItemSelection(itemId) {
                if (!itemId || isAdding) return;
                isAdding = true;

                const item = findItem(itemId);
                if (!item) {
                    isAdding = false;
                    return;
                }

                const qty = parseInt($('#item_qty').val(), 10) || 1;
                const customPrice = $('#item_price').val();
                addItemToCart(item, qty, customPrice);

                setTimeout(function () {
                    if (window.AthaSearchableSelect) {
                        AthaSearchableSelect.clear('#item_select');
                    } else {
                        $('#item_select').val(null);
                    }
                    isAdding = false;
                }, 100);
            }

            $('#item_select').on('select2:select', function (e) {
                const itemId = e.params && e.params.data ? e.params.data.id : $(this).val();
                handleItemSelection(itemId);
            });

            $('#item_select').on('change', function () {
                const itemId = $(this).val();
                handleItemSelection(itemId);
            });

            $('#btn-add-item').on('click', function () {
                const itemId = $('#item_select').val();
                const qty = parseInt($('#item_qty').val(), 10) || 1;
                const customPrice = $('#item_price').val();
                const item = findItem(itemId);

                if (!item) {
                    Swal.fire({ icon: 'warning', title: 'Pilih barang terlebih dahulu.' });
                    return;
                }

                addItemToCart(item, qty, customPrice);
                if (window.AthaSearchableSelect) {
                    AthaSearchableSelect.clear('#item_select');
                } else {
                    $('#item_select').val(null);
                }
            });

            $('#item_qty, #item_price').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#btn-add-item').click();
                }
            });

            $itemsBody.on('click', '.btn-remove-item', function () {
                const index = $(this).data('index');
                const line = cart[index];
                if (!line) {
                    return;
                }

                if (line.quantity > getCurrentStock(line.item_id)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok tidak cukup',
                        text: 'Barang ini tidak bisa dihapus karena stok tersedia tidak cukup untuk rollback.',
                    });
                    return;
                }

                cart.splice(index, 1);
                renderCart();
            });

            function handleQtyInput($input, showError) {
                const index = parseInt($input.data('index'), 10);
                const line = cart[index];
                if (!line) {
                    return false;
                }

                const qty = parseInt($input.val(), 10);
                if (isNaN(qty) || qty < 1) {
                    if (showError) {
                        $input.val(line.quantity);
                        Swal.fire({ icon: 'warning', title: 'Qty harus minimal 1.' });
                    }
                    return false;
                }

                const minQty = minAllowedQty(line);
                if (qty < minQty) {
                    if (showError) {
                        $input.val(line.quantity);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok tidak cukup',
                            text: 'Qty minimal ' + minQty + ' (stok tersedia tidak cukup untuk mengurangi lebih banyak).',
                        });
                    }
                    return false;
                }

                line.quantity = qty;
                recalcLine(line);
                $input.val(qty);
                $input.closest('tr').find('.cart-subtotal').text(formatRp(line.subtotal));
                updateSummary();
                return true;
            }

            function handlePriceInput($input, showError) {
                const index = parseInt($input.data('index'), 10);
                const line = cart[index];
                if (!line) {
                    return false;
                }

                const price = parseFloat($input.val());
                if (isNaN(price) || price < 0) {
                    if (showError) {
                        $input.val(line.unit_price);
                        Swal.fire({ icon: 'warning', title: 'Harga beli tidak valid (minimal 0).' });
                    }
                    return false;
                }

                line.unit_price = price;
                recalcLine(line);
                $input.closest('tr').find('.cart-subtotal').text(formatRp(line.subtotal));
                updateSummary();
                return true;
            }

            function validateCart() {
                for (let i = 0; i < cart.length; i++) {
                    if (cart[i].quantity < 1) {
                        Swal.fire({ icon: 'warning', title: 'Qty barang tidak valid.', text: cart[i].name });
                        return false;
                    }
                    if (isNaN(cart[i].unit_price) || cart[i].unit_price < 0) {
                        Swal.fire({ icon: 'warning', title: 'Harga beli tidak valid.', text: cart[i].name });
                        return false;
                    }
                }
                return true;
            }

            $itemsBody.on('input', '.cart-qty-input', function () {
                handleQtyInput($(this), false);
            });

            $itemsBody.on('blur', '.cart-qty-input', function () {
                handleQtyInput($(this), true);
            });

            $itemsBody.on('input', '.cart-price-input', function () {
                handlePriceInput($(this), false);
            });

            $itemsBody.on('blur', '.cart-price-input', function () {
                handlePriceInput($(this), true);
            });

            $('#purchase-form').on('submit', function (e) {
                e.preventDefault();
                if (!cart.length) {
                    return;
                }

                if (!validateCart()) {
                    return;
                }

                if ($('#payment_method').val() === 'transfer' && !$('#bank_account_id').val()) {
                    Swal.fire({ icon: 'warning', title: 'Pilih akun bank untuk transfer.' });
                    return;
                }

                $submit.prop('disabled', true);

                $.ajax({
                    url: settings.updateUrl,
                    type: 'POST',
                    headers: { Accept: 'application/json' },
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'PUT',
                        discount: $('#discount').val() || 0,
                        notes: $('#notes').val(),
                        payment_method: $('#payment_method').val(),
                        bank_account_id: $('#bank_account_id').val() || null,
                        items: cart.map(function (l) {
                            return {
                                item_id: l.item_id,
                                quantity: l.quantity,
                                unit_price: l.unit_price,
                            };
                        }),
                    },
                    success: function (res) {
                        Swal.fire({
                            icon: 'success',
                            title: res.message,
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(function () {
                            window.location.href = settings.redirectUrl;
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Gagal menyimpan.' });
                        $submit.prop('disabled', false);
                        updateSummary();
                    },
                });
            });

            renderCart();
        },
    };
})(jQuery);
