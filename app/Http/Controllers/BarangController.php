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
                    'item_name as nama',
                    'meins as satuan',
                    DB::raw("CASE 
                        WHEN mtart = 'FERT' THEN 'Finished Product'
                        WHEN mtart = 'HAWA' THEN 'Trading Goods'
                        WHEN mtart = 'ROH' THEN 'Raw Material'
                        ELSE COALESCE(mtart, 'Sparepart')
                    END as kategori")
                )
                ->where('item_name', 'ilike', "%{$searchTerm}%")
                ->orWhere('code', 'ilike', "%{$searchTerm}%")
                ->orderBy('item_name')
                ->limit(20)
                ->get();

            // Mapping satuan default jika NULL
            $results = $results->map(function($item) {
                $item->satuan = $this->mapSatuan($item->satuan ?? 'pcs');
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