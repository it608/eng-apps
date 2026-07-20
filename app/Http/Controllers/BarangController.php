<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    /**
     * Search barang dari database PostgreSQL
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $materialType = $this->normalizeMaterialType($request->get('material_type', 'sparepart'));
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            // Escape karakter khusus untuk LIKE query
            $searchTerm = $this->escapeLike($query);
            
            $results = DB::connection('pgsql2')
                ->table('tb_skb080_1mmara')
                ->select(
                    'id_items as id',
                    'code as kode',
                    'item_name as nama',
                    'meins as satuan',
                    DB::raw("CASE 
                        WHEN mtart = 'FERT' THEN 'Finished Product'
                        WHEN mtart = 'HAWA' THEN 'Trading Goods'
                        WHEN mtart = 'ROH' THEN 'Raw Material'
                        ELSE COALESCE(mtart, 'Sparepart')
                    END as kategori")
                )
                ->where(function ($query) use ($materialType) {
                    $this->applyMaterialScope($query, $materialType);
                })
                ->where(function ($query) use ($searchTerm) {
                    $query->where('item_name', 'ilike', "%{$searchTerm}%")
                        ->orWhere('code', 'ilike', "%{$searchTerm}%");
                })
                ->orderBy('item_name')
                ->limit(20)
                ->get();

            // Mapping satuan default jika NULL
            $results = $results->map(function($item) use ($materialType) {
                $item->satuan = $this->mapSatuan($item->satuan ?? 'pcs');
                $item->material_type = $materialType;
                $item->material_type_label = $materialType === 'non_sparepart' ? 'Non Sparepart' : 'Sparepart';
                return $item;
            });

            // Log untuk debugging (bisa dihapus kalau sudah produksi)
            \Log::info('Search term: ' . $query . ' | Escaped: ' . $searchTerm . ' | Results: ' . $results->count());
            
            return response()->json($results);

        } catch (\Exception $e) {
            \Log::error('Search error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Escape karakter khusus untuk LIKE query
     */
    private function escapeLike($string)
    {
        $search = ['%', '_', '\\'];
        $replace = ['\\%', '\\_', '\\\\'];
        return str_replace($search, $replace, $string);
    }

    private function normalizeMaterialType(?string $value): string
    {
        return in_array($value, ['sparepart', 'non_sparepart'], true) ? $value : 'sparepart';
    }

    private function sparepartMaterialPrefixes(): array
    {
        return ['YSPR'];
    }

    private function applyMaterialScope($query, string $materialType, string $mtartColumn = 'mtart', string $codeColumn = 'code'): void
    {
        $prefixes = $this->sparepartMaterialPrefixes();

        if ($materialType === 'non_sparepart') {
            $query->where(function ($scope) use ($prefixes, $mtartColumn) {
                $scope->whereNull($mtartColumn)
                    ->orWhereNotIn(DB::raw('UPPER(TRIM(' . $mtartColumn . '))'), $prefixes);
            });

            foreach ($prefixes as $prefix) {
                $query->where(function ($scope) use ($codeColumn, $prefix) {
                    $scope->whereNull($codeColumn)
                        ->orWhere(DB::raw('UPPER(TRIM(' . $codeColumn . '))'), 'NOT LIKE', $prefix . '%');
                });
            }

            return;
        }

        $query->where(function ($scope) use ($prefixes, $mtartColumn, $codeColumn) {
            $scope->whereIn(DB::raw('UPPER(TRIM(' . $mtartColumn . '))'), $prefixes);

            foreach ($prefixes as $prefix) {
                $scope->orWhere(DB::raw('UPPER(TRIM(' . $codeColumn . '))'), 'LIKE', $prefix . '%');
            }
        });
    }

    private function applySparepartMaterialScope($query, string $mtartColumn = 'mtart', string $codeColumn = 'code'): void
    {
        $this->applyMaterialScope($query, 'sparepart', $mtartColumn, $codeColumn);
    }

    /**
     * Mapping satuan dari SAP ke format yang lebih user friendly
     */
    private function mapSatuan($meins)
    {
        if (empty($meins)) {
            return 'pcs';
        }
        
        $satuanMap = [
            'PCS' => 'pcs', 'PC' => 'pcs',
            'UNIT' => 'unit', 'UNT' => 'unit',
            'KG' => 'kg', 'KGM' => 'kg',
            'GRAM' => 'gram', 'G' => 'gram',
            'L' => 'liter', 'LT' => 'liter', 'LTR' => 'liter',
            'ML' => 'ml',
            'M' => 'meter', 'MTR' => 'meter',
            'CM' => 'cm',
            'MM' => 'mm',
            'BOX' => 'box', 'BOK' => 'box',
            'PACK' => 'pack', 'PK' => 'pack',
            'ROLL' => 'roll', 'ROL' => 'roll',
            'SET' => 'set',
            'BTL' => 'bottle',
            'CAN' => 'can',
            'TUBE' => 'tube',
            'DR' => 'drum', 'DRUM' => 'drum',
            'PLT' => 'pallet', 'PAL' => 'pallet',
        ];

        $meinsUpper = strtoupper(trim($meins));
        return $satuanMap[$meinsUpper] ?? strtolower($meins);
    }
}
