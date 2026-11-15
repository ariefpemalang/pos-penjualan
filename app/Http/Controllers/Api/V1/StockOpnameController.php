<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    use RespondsWithApi;

    public function __construct(private StockService $stockService)
    {
        $this->middleware('permission:stock opname view')->only('index');
        $this->middleware('permission:stock opname create')->only('saveDraft');
        $this->middleware('permission:stock opname adjust')->only(['approve', 'reject']);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $canAdjust = $user && ($user->hasRole('Super Admin') || $user->can('stock opname adjust'));

        $pending = StockOpname::with(['creator:id,name', 'items.item:id,code,name,stock,purchase_price'])
            ->where('status', 'pending_approval')
            ->latest()
            ->first();

        $pendingData = null;
        if ($pending) {
            $pendingData = [
                'id' => $pending->id,
                'opname_number' => $pending->opname_number,
                'status' => $pending->status,
                'creator' => $pending->creator?->name ?? 'Staff',
                'created_at' => $pending->created_at?->toIso8601String(),
                'notes' => $pending->notes,
                'total_items' => (int) $pending->total_items,
                'total_loss_units' => (int) $pending->total_loss_units,
                'total_loss_cost' => (float) $pending->total_loss_cost,
                'total_gain_units' => (int) $pending->total_gain_units,
                'total_gain_cost' => (float) $pending->total_gain_cost,
                'items' => $pending->items->map(function (StockOpnameItem $it) {
                    return [
                        'id' => $it->item_id,
                        'name' => $it->item?->name ?? 'Barang',
                        'code' => $it->item?->code ?? '-',
                        'system_stock' => (int) $it->system_stock,
                        'physical_stock' => (int) $it->physical_stock,
                        'difference' => (int) $it->difference,
                        'difference_cost' => (float) $it->difference_cost,
                        'notes' => (string) ($it->notes ?? ''),
                    ];
                })->values(),
            ];
        }

        $recent = StockOpname::with(['creator:id,name', 'approver:id,name'])
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function (StockOpname $op) {
                return [
                    'id' => $op->id,
                    'opname_number' => $op->opname_number,
                    'status' => $op->status,
                    'creator' => $op->creator?->name ?? '-',
                    'approver' => $op->approver?->name ?? '-',
                    'created_at' => $op->created_at?->toIso8601String(),
                    'approved_at' => $op->approved_at?->toIso8601String(),
                    'total_items' => (int) $op->total_items,
                    'total_loss_units' => (int) $op->total_loss_units,
                    'total_loss_cost' => (float) $op->total_loss_cost,
                    'total_gain_units' => (int) $op->total_gain_units,
                    'total_gain_cost' => (float) $op->total_gain_cost,
                ];
            });

        return $this->apiSuccess('Data stock opname berhasil diambil.', [
            'can_adjust' => $canAdjust,
            'pending_opname' => $pendingData,
            'recent_opnames' => $recent,
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:items,id'],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'general_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $userId = (int) $request->user()->id;

        $savedOpname = DB::transaction(function () use ($validated, $userId) {
            $opname = StockOpname::where('status', 'pending_approval')->latest()->first();
            if (! $opname) {
                $opname = StockOpname::create([
                    'opname_number' => 'OPN-' . date('Ymd-His'),
                    'status' => 'pending_approval',
                    'created_by' => $userId,
                    'notes' => $validated['general_notes'] ?? null,
                ]);
            } else {
                $opname->update([
                    'created_by' => $userId,
                    'notes' => $validated['general_notes'] ?? $opname->notes,
                ]);
                $opname->items()->delete();
            }

            $totalLossUnits = 0;
            $totalLossCost = 0.0;
            $totalGainUnits = 0;
            $totalGainCost = 0.0;
            $itemCount = 0;

            foreach ($validated['items'] as $entry) {
                $item = Item::find($entry['id']);
                if (! $item) {
                    continue;
                }

                $sysStock = (int) $item->stock;
                $physStock = (int) $entry['physical_stock'];
                $diff = $physStock - $sysStock;
                $cost = (float) $item->purchase_price;
                $diffCost = $diff * $cost;

                if ($diff < 0) {
                    $totalLossUnits += abs($diff);
                    $totalLossCost += abs($diffCost);
                } elseif ($diff > 0) {
                    $totalGainUnits += $diff;
                    $totalGainCost += $diffCost;
                }

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'item_id' => $item->id,
                    'system_stock' => $sysStock,
                    'physical_stock' => $physStock,
                    'difference' => $diff,
                    'purchase_price' => $cost,
                    'difference_cost' => $diffCost,
                    'notes' => trim((string) ($entry['notes'] ?? '')) ?: null,
                ]);

                $itemCount++;
            }

            $opname->update([
                'total_items' => $itemCount,
                'total_loss_units' => $totalLossUnits,
                'total_loss_cost' => $totalLossCost,
                'total_gain_units' => $totalGainUnits,
                'total_gain_cost' => $totalGainCost,
            ]);

            return $opname;
        });

        return $this->apiSuccess('Stok opname berhasil disimpan dan diajukan ke Owner untuk ditinjau.', [
            'opname_id' => $savedOpname->id,
            'opname_number' => $savedOpname->opname_number,
            'total_items' => $savedOpname->total_items,
            'loss_units' => $savedOpname->total_loss_units,
            'loss_cost' => $savedOpname->total_loss_cost,
            'gain_units' => $savedOpname->total_gain_units,
            'gain_cost' => $savedOpname->total_gain_cost,
        ]);
    }

    public function approve(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user || (! $user->hasRole('Super Admin') && ! $user->can('stock opname adjust'))) {
            return $this->apiError('Akses ditolak! Anda tidak memiliki izin untuk menyetujui penyesuaian stok barang.', 403);
        }

        $validated = $request->validate([
            'stock_opname_id' => ['nullable', 'exists:stock_opnames,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:items,id'],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = (int) $user->id;
        $adjustments = [];
        $lossCount = 0;
        $lossUnits = 0;
        $lossAmount = 0.0;
        $gainCount = 0;
        $gainUnits = 0;
        $gainAmount = 0.0;

        DB::transaction(function () use (
            $validated,
            $userId,
            &$adjustments,
            &$lossCount,
            &$lossUnits,
            &$lossAmount,
            &$gainCount,
            &$gainUnits,
            &$gainAmount
        ) {
            $opname = null;
            if (! empty($validated['stock_opname_id'])) {
                $opname = StockOpname::find($validated['stock_opname_id']);
            }

            $refNo = $opname ? $opname->opname_number : ('OPN-' . date('Ymd-His'));

            foreach ($validated['items'] as $entry) {
                $item = Item::lockForUpdate()->find($entry['id']);
                if (! $item) {
                    continue;
                }

                $systemStock = (int) $item->stock;
                $physicalStock = (int) $entry['physical_stock'];
                $diff = $physicalStock - $systemStock;

                if ($diff === 0) {
                    $item->update(['stock_opname' => $physicalStock]);
                    continue;
                }

                $userNotes = trim((string) ($entry['notes'] ?? ''));

                if ($diff < 0) {
                    $lossQty = abs($diff);
                    $defaultNote = "Stock Opname: Selisih fisik -{$lossQty} (Barang Hilang / Rusak)";
                    $note = $userNotes !== '' ? "Stock Opname: {$userNotes}" : $defaultNote;

                    $this->stockService->stockOut(
                        $item->id,
                        $lossQty,
                        $userId,
                        $refNo,
                        $note
                    );

                    $lossCount++;
                    $lossUnits += $lossQty;
                    $lossAmount += ($lossQty * (float) $item->purchase_price);
                } else {
                    $gainQty = $diff;
                    $defaultNote = "Stock Opname: Selisih fisik +{$gainQty} (Kelebihan / Temuan Fisik)";
                    $note = $userNotes !== '' ? "Stock Opname: {$userNotes}" : $defaultNote;

                    $this->stockService->stockIn(
                        $item->id,
                        $gainQty,
                        $userId,
                        $refNo,
                        $note
                    );

                    $gainCount++;
                    $gainUnits += $gainQty;
                    $gainAmount += ($gainQty * (float) $item->purchase_price);
                }

                $item->update(['stock_opname' => $physicalStock]);

                $adjustments[] = [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'diff' => $diff,
                ];
            }

            if ($opname) {
                $opname->update([
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'total_loss_units' => $lossUnits,
                    'total_loss_cost' => $lossAmount,
                    'total_gain_units' => $gainUnits,
                    'total_gain_cost' => $gainAmount,
                ]);
            }
        });

        $message = 'Stok opname berhasil disetujui Owner dan stok sistem telah diperbarui.';
        if ($lossCount > 0) {
            $message .= " Tercatat {$lossCount} barang berkurang (-{$lossUnits} unit).";
        }
        if ($gainCount > 0) {
            $message .= " Tercatat {$gainCount} barang bertambah (+{$gainUnits} unit).";
        }

        return $this->apiSuccess($message, [
            'adjusted_items' => count($adjustments),
            'loss_count' => $lossCount,
            'loss_units' => $lossUnits,
            'loss_amount' => $lossAmount,
            'gain_count' => $gainCount,
            'gain_units' => $gainUnits,
            'gain_amount' => $gainAmount,
        ]);
    }

    public function reject(Request $request, StockOpname $stockOpname): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user || (! $user->hasRole('Super Admin') && ! $user->can('stock opname adjust'))) {
            return $this->apiError('Akses ditolak! Anda tidak memiliki izin untuk menolak pengajuan opname.', 403);
        }

        if ($stockOpname->status !== 'pending_approval') {
            return $this->apiError('Status opname ini tidak dalam posisi menunggu persetujuan.', 422);
        }

        $stockOpname->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $this->apiSuccess('Pengajuan stock opname telah ditolak.');
    }
}
