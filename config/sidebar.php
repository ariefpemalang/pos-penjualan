<?php

return [
    'menus' => [
        [
            'title' => 'Dashboard',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'icon' => 'bi-speedometer',
                    'route' => 'dashboard',
                    'permission' => 'dashboard view',
                ],
            ],
        ],
        [
            'title' => 'Transaksi',
            'items' => [
                [
                    'label' => 'Transaksi',
                    'icon' => 'bi-receipt-cutoff',
                    'permissions' => ['transaction view', 'purchase view', 'purchase create'],
                    'submenus' => [
                        [
                            'label' => 'Transaksi Penjualan',
                            'route' => 'transactions.index',
                            'permission' => 'transaction view',
                        ],
                        [
                            'label' => 'Transaksi Pembelian',
                            'route' => 'purchases.index',
                            'permission' => 'purchase view',
                        ],
                        [
                            'label' => 'Pembelian Barang',
                            'route' => 'purchases.create',
                            'permission' => 'purchase create',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Supplier',
            'items' => [
                [
                    'label' => 'Supplier',
                    'icon' => 'bi-truck',
                    'route' => 'suppliers.index',
                    'permission' => 'supplier view',
                ],
            ],
        ],
        [
            'title' => 'Inventory',
            'items' => [
                [
                    'label' => 'Inventory',
                    'icon' => 'bi-box-seam',
                    'permissions' => [
                        'item view', 'item category view', 'item unit view',
                        'stock in view', 'stock out view', 'stock report view',
                        'stock opname view',
                    ],
                    'submenus' => [
                        [
                            'label' => 'Data Barang',
                            'route' => 'items.index',
                            'permission' => 'item view',
                        ],
                        [
                            'label' => 'Kategori Barang',
                            'route' => 'item-categories.index',
                            'permission' => 'item category view',
                        ],
                        [
                            'label' => 'Satuan Barang',
                            'route' => 'item-units.index',
                            'permission' => 'item unit view',
                        ],
                        [
                            'label' => 'Stok Masuk',
                            'route' => 'stock-ins.index',
                            'permission' => 'stock in view',
                        ],
                        [
                            'label' => 'Stok Keluar',
                            'route' => 'stock-outs.index',
                            'permission' => 'stock out view',
                        ],
                        [
                            'label' => 'Laporan Stok',
                            'route' => 'stock-reports.index',
                            'permission' => 'stock report view',
                        ],
                        [
                            'label' => 'Stock Opname',
                            'route' => 'stock-opnames.index',
                            'permission' => 'stock opname view',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Keuangan',
            'items' => [
                [
                    'label' => 'Keuangan',
                    'icon' => 'bi-file-earmark-text',
                    'permissions' => [
                        'sales report view',
                        'financial report view',
                        'asset report view',
                        'manual income view',
                        'manual expense view',
                        'transaction view',
                        'purchase view',
                        'purchase create',
                    ],
                    'submenus' => [
                        [
                            'label' => 'Laporan Keuangan',
                            'route' => 'financial-reports.index',
                            'permission' => 'financial report view',
                        ],
                        [
                            'label' => 'Laporan Kategori',
                            'route' => 'asset-reports.index',
                            'permission' => 'asset report view',
                        ],
                        [
                            'label' => 'Laporan Penjualan',
                            'submenus' => [
                                [
                                    'label' => 'Transaksi',
                                    'route' => 'sales-reports.index',
                                    'permission' => 'financial report view',
                                ],
                                [
                                    'label' => 'Penjualan Barang',
                                    'route' => 'item-sales-reports.index',
                                    'permission' => 'financial report view',
                                ],
                                [
                                    'label' => 'Penjualan Kategori',
                                    'route' => 'category-sales-reports.index',
                                    'permission' => 'financial report view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Laporan Pembelian',
                            'submenus' => [
                                [
                                    'label' => 'Data Pembelian',
                                    'route' => 'purchases.index',
                                    'permission' => 'purchase view',
                                ],
                            ],
                        ],
                        [
                            'label' => 'Pemasukan Manual',
                            'route' => 'manual-incomes.index',
                            'permission' => 'manual income view',
                        ],
                        [
                            'label' => 'Pengeluaran Manual',
                            'route' => 'manual-expenses.index',
                            'permission' => 'manual expense view',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Pengaturan',
            'items' => [
                [
                    'label' => 'Pengaturan Aplikasi',
                    'icon' => 'bi-gear',
                    'route' => 'settings.edit',
                    'permission' => 'settings view',
                ],
            ],
        ],
        [
            'title' => 'Pengguna',
            'items' => [
                [
                    'label' => 'Users & Roles',
                    'icon' => 'bi-people',
                    'permissions' => ['user view', 'role view'],
                    'submenus' => [
                        [
                            'label' => 'Data User',
                            'route' => 'users.index',
                            'permission' => 'user view',
                        ],
                        [
                            'label' => 'Role & Permission',
                            'route' => 'roles.index',
                            'permission' => 'role view',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
