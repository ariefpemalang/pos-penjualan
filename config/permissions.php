<?php

return [
    [
        'group' => 'Dashboard',
        'access' => [
            'dashboard view',
            'dashboard finance view',
            'dashboard low stock view',
            'dashboard items view',
        ],
    ],
    [
        'group' => 'User',
        'access' => ['user view', 'user create', 'user edit', 'user delete'],
    ],
    [
        'group' => 'Role',
        'access' => ['role view', 'role create', 'role edit', 'role delete'],
    ],
    [
        'group' => 'Supplier',
        'access' => ['supplier view', 'supplier create', 'supplier edit', 'supplier delete'],
    ],
    [
        'group' => 'Kategori Barang',
        'access' => ['item category view', 'item category create', 'item category edit', 'item category delete'],
    ],
    [
        'group' => 'Satuan Barang',
        'access' => ['item unit view', 'item unit create', 'item unit edit', 'item unit delete'],
    ],
    [
        'group' => 'Barang / Inventory',
        'access' => ['item view', 'item create', 'item edit', 'item delete'],
    ],
    [
        'group' => 'Stok Masuk',
        'access' => ['stock in view', 'stock in create'],
    ],
    [
        'group' => 'Stok Keluar',
        'access' => ['stock out view', 'stock out create'],
    ],
    [
        'group' => 'Laporan Stok',
        'access' => ['stock report view'],
    ],
    [
        'group' => 'Stock Opname',
        'access' => [
            'stock opname view',
            'stock opname create',
            'stock opname adjust',
        ],
    ],
    [
        'group' => 'Transaksi',
        'access' => ['transaction view', 'transaction create', 'transaction edit', 'transaction delete'],
    ],
    [
        'group' => 'Pembelian',
        'access' => ['purchase view', 'purchase create', 'purchase edit', 'purchase delete'],
    ],
    [
        'group' => 'Laporan & Analisis',
        'access' => [
            'financial report view',
            'asset report view',
            'sales report view',
            'item sales report view',
            'category sales report view',
            'purchase report view',
        ],
    ],
    [
        'group' => 'Pemasukan Manual',
        'access' => ['manual income view', 'manual income create', 'manual income cancel'],
    ],
    [
        'group' => 'Pengeluaran Manual',
        'access' => ['manual expense view', 'manual expense create', 'manual expense cancel'],
    ],
    [
        'group' => 'Pengaturan Aplikasi',
        'access' => ['settings view', 'settings edit'],
    ],
];
