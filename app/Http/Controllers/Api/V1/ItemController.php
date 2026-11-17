<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\PaginatesApiQuery;
use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Concerns\HandlesEditableEntityCode;
use App\Http\Controllers\Concerns\HandlesEntityPhoto;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    use HandlesEditableEntityCode, HandlesEntityPhoto;
    use PaginatesApiQuery;
    use RespondsWithApi;

    public function __construct()
    {
        $this->middleware('permission:item view')->only(['index', 'show']);
        $this->middleware('permission:item create')->only('store');
        $this->middleware('permission:item edit')->only('update');
        $this->middleware('permission:item delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Item::query()->with(['category:id,name', 'unit:id,name,abbreviation']);

        $categoryId = $request->integer('category_id');
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $unitId = $request->integer('unit_id');
        if ($unitId > 0) {
            $query->where('unit_id', $unitId);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $paginator = $this->paginateQuery(
            $request,
            $query,
            ['name', 'code', 'description'],
            [],
            15,
            500,
        );

        return $this->apiSuccess('Daftar barang berhasil diambil.', [
            'items' => ItemResource::collection($paginator->items())->resolve(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'unit_id' => ['required', 'exists:item_units,id'],
            'stock_opname' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->entityCodeRules('items', 50), $this->photoRules()));

        $item = Item::create([
            ...collect($validated)->except(['photo', 'remove_photo', 'code'])->all(),
            'code' => $this->resolveEntityCode($request, Item::class, $validated['code'] ?? null),
            'photo' => $this->resolvePhotoPath($request, 'photos/items'),
            'stock' => 0,
            'stock_opname' => $validated['stock_opname'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? 0,
            'selling_price' => $validated['selling_price'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return $this->apiSuccess('Barang berhasil ditambahkan.', [
            'item' => new ItemResource($item),
        ], 201);
    }

    public function show(Item $item): JsonResponse
    {
        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return $this->apiSuccess('Detail barang berhasil diambil.', [
            'item' => new ItemResource($item),
        ]);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'unit_id' => ['required', 'exists:item_units,id'],
            'stock_opname' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->entityCodeRules('items', 50, $item->id, true), $this->photoRules()));

        $item->update([
            ...collect($validated)->except(['photo', 'remove_photo', 'code'])->all(),
            'code' => trim($validated['code']),
            'photo' => $this->resolvePhotoPath($request, 'photos/items', $item->photo),
            'stock_opname' => $validated['stock_opname'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? 0,
            'selling_price' => $validated['selling_price'] ?? 0,
            'is_active' => array_key_exists('is_active', $validated)
                ? $request->boolean('is_active')
                : $item->is_active,
        ]);

        $item->load(['category:id,name', 'unit:id,name,abbreviation']);

        return $this->apiSuccess('Barang berhasil diperbarui.', [
            'item' => new ItemResource($item->fresh()->load(['category:id,name', 'unit:id,name,abbreviation'])),
        ]);
    }

    public function destroy(Item $item): JsonResponse
    {
        if ($item->stock > 0) {
            return $this->apiError('Barang masih memiliki stok. Kosongkan stok terlebih dahulu.', 422);
        }

        if ($item->stockMovements()->exists()) {
            return $this->apiError('Barang memiliki riwayat stok dan tidak dapat dihapus.', 422);
        }

        $item->deletePhotoFile();
        $item->delete();

        return $this->apiSuccess('Barang berhasil dihapus.');
    }
}
