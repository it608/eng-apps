<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of work orders.
     */
    public function index()
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval'; // Sesuaikan dengan role di sistem lo
            
            // Get counts berdasarkan role
            if ($isApproval) {
                // Approval lihat semua
                $counts = [
                    'total' => DB::table('trWorkOrder')->count(),
                    'draft' => DB::table('trWorkOrder')->where('status', 'draft')->count(),
                    'submitted' => DB::table('trWorkOrder')->where('status', 'submitted')->count(),
                    'approved' => DB::table('trWorkOrder')->where('status', 'approved')->count(),
                    'rejected' => DB::table('trWorkOrder')->where('status', 'rejected')->count(),
                    'completed' => DB::table('trWorkOrder')->where('status', 'completed')->count(),
                ];
            } else {
                // User biasa lihat punya dia aja
                $counts = [
                    'total' => DB::table('trWorkOrder')->where('created_by', $user->id)->count(),
                    'draft' => DB::table('trWorkOrder')->where('created_by', $user->id)->where('status', 'draft')->count(),
                    'submitted' => DB::table('trWorkOrder')->where('created_by', $user->id)->where('status', 'submitted')->count(),
                    'approved' => DB::table('trWorkOrder')->where('created_by', $user->id)->where('status', 'approved')->count(),
                    'rejected' => DB::table('trWorkOrder')->where('created_by', $user->id)->where('status', 'rejected')->count(),
                    'completed' => DB::table('trWorkOrder')->where('created_by', $user->id)->where('status', 'completed')->count(),
                ];
            }
            
            return view('user.workorder', compact('counts', 'isApproval'));
        } catch (\Exception $e) {
            Log::error('WorkOrder index error: ' . $e->getMessage());
            return view('user.workorder')->with('error', 'Gagal memuat halaman: ' . $e->getMessage());
        }
    }

    /**
     * Get work order data for AJAX/datatable.
     */
    public function getData(Request $request)
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval';
            
            $query = DB::table('trWorkOrder')
                ->select([
                    'trWorkOrder.id',
                    'trWorkOrder.nomor',
                    'trWorkOrder.judul',
                    'trWorkOrder.deskripsi',
                    'trWorkOrder.file_name',
                    'trWorkOrder.file_path',
                    'trWorkOrder.status',
                    'trWorkOrder.created_by',
                    'trWorkOrder.created_at',
                    'trWorkOrder.updated_at',
                    'trWorkOrder.rejection_notes',
                    'users.name as created_by_name',
                    'users.email as created_by_email'
                ])
                ->leftJoin('users', 'trWorkOrder.created_by', '=', 'users.id');

            // Filter berdasarkan role
            if (!$isApproval) {
                // User biasa: hanya lihat punya dia
                $query->where('trWorkOrder.created_by', $user->id);
            }

            // Filter search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('trWorkOrder.nomor', 'LIKE', "%{$search}%")
                      ->orWhere('trWorkOrder.judul', 'LIKE', "%{$search}%")
                      ->orWhere('trWorkOrder.deskripsi', 'LIKE', "%{$search}%");
                });
            }

            // Filter status
            if ($request->filled('status')) {
                $query->where('trWorkOrder.status', $request->status);
            }

            // Filter column
            if ($request->filled('filter_nomor')) {
                $query->where('trWorkOrder.nomor', 'LIKE', '%' . $request->filter_nomor . '%');
            }
            
            if ($request->filled('filter_judul')) {
                $query->where('trWorkOrder.judul', 'LIKE', '%' . $request->filter_judul . '%');
            }
            
            if ($request->filled('filter_status')) {
                $query->where('trWorkOrder.status', $request->filter_status);
            }

            // Filter created_by (nama pembuat) - KHUSUS APPROVAL
            if ($request->filled('filter_created_by') && $isApproval) {
                $query->where('users.name', 'LIKE', '%' . $request->filter_created_by . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $data = $query->orderBy('trWorkOrder.created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage()
                ],
                'isApproval' => $isApproval
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder getData error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progress data for approved work orders
     */
    public function progressData(Request $request)
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval';
            
            $query = DB::table('trWorkOrder')
                ->select([
                    'trWorkOrder.id',
                    'trWorkOrder.nomor',
                    'trWorkOrder.judul',
                    'trWorkOrder.deskripsi',
                    'trWorkOrder.status as main_status',
                    'trWorkOrder.progress_status',
                    'trWorkOrder.open_at',
                    'trWorkOrder.progress_at',
                    'trWorkOrder.closed_at',
                    'trWorkOrder.created_at',
                    'trWorkOrder.updated_at',
                    'trWorkOrder.approved_at',
                    'users.name as created_by_name',
                ])
                ->leftJoin('users', 'trWorkOrder.created_by', '=', 'users.id')
                ->where('trWorkOrder.status', 'approved'); // Hanya yang sudah approved

            // Filter berdasarkan role
            if (!$isApproval) {
                // User biasa: hanya lihat punya dia
                $query->where('trWorkOrder.created_by', $user->id);
            }

            // Filter search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('trWorkOrder.nomor', 'LIKE', "%{$search}%")
                      ->orWhere('trWorkOrder.judul', 'LIKE', "%{$search}%");
                });
            }

            // Filter progress status
            if ($request->filled('progress_status')) {
                if ($request->progress_status === 'open') {
                    // Open: approved tapi belum punya progress_status atau progress_status = 'open'
                    $query->where(function($q) {
                        $q->whereNull('trWorkOrder.progress_status')
                          ->orWhere('trWorkOrder.progress_status', 'open');
                    });
                } else {
                    $query->where('trWorkOrder.progress_status', $request->progress_status);
                }
            } else {
                // Default: tampilkan semua yang approved (termasuk yang belum punya progress_status)
                $query->where(function($q) {
                    $q->whereNotNull('trWorkOrder.progress_status')
                      ->orWhereNull('trWorkOrder.progress_status');
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $data = $query->orderBy('trWorkOrder.approved_at', 'desc')->paginate($perPage);

            // Process data untuk set default progress_status
            $items = collect($data->items())->map(function($item) {
                if (is_null($item->progress_status)) {
                    $item->progress_status = 'open'; // Default untuk yang baru approved
                }
                return $item;
            });

            // Get summary counts
            $summary = [
                'open' => DB::table('trWorkOrder')
                    ->where('status', 'approved')
                    ->where(function($q) {
                        $q->whereNull('progress_status')
                          ->orWhere('progress_status', 'open');
                    })
                    ->when(!$isApproval, function($q) use ($user) {
                        return $q->where('created_by', $user->id);
                    })
                    ->count(),
                'progress' => DB::table('trWorkOrder')
                    ->where('status', 'approved')
                    ->where('progress_status', 'progress')
                    ->when(!$isApproval, function($q) use ($user) {
                        return $q->where('created_by', $user->id);
                    })
                    ->count(),
                'closed' => DB::table('trWorkOrder')
                    ->where('status', 'approved')
                    ->where('progress_status', 'closed')
                    ->when(!$isApproval, function($q) use ($user) {
                        return $q->where('created_by', $user->id);
                    })
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage()
                ],
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder progressData error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update progress status
     */
    public function updateProgress(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'progress_status' => 'required|in:open,progress,closed',
                'progress_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Cek apakah sudah approved
            if ($wo->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order yang sudah approved yang bisa diupdate progressnya'
                ], 422);
            }

            $updateData = [
                'progress_status' => $request->progress_status,
                'progress_notes' => $request->progress_notes,
                'updated_at' => now()
            ];

            // Set timestamp sesuai status
            if ($request->progress_status === 'open') {
                if (!$wo->open_at) {
                    $updateData['open_at'] = now();
                }
                // Reset progress_at dan closed_at kalau balik ke open
                $updateData['progress_at'] = null;
                $updateData['closed_at'] = null;
            } 
            else if ($request->progress_status === 'progress') {
                if (!$wo->progress_at) {
                    $updateData['progress_at'] = now();
                }
                // Kalau dari open ke progress, pastikan open_at terisi
                if (!$wo->open_at) {
                    $updateData['open_at'] = now();
                }
                $updateData['closed_at'] = null;
            } 
            else if ($request->progress_status === 'closed') {
                if (!$wo->closed_at) {
                    $updateData['closed_at'] = now();
                }
                // Kalau langsung ke closed, pastikan open_at dan progress_at terisi
                if (!$wo->open_at) {
                    $updateData['open_at'] = now();
                }
                if (!$wo->progress_at) {
                    $updateData['progress_at'] = now();
                }
            }

            DB::table('trWorkOrder')
                ->where('id', $id)
                ->update($updateData);

            // Hitung lead time (dalam jam)
            $updatedWo = DB::table('trWorkOrder')->where('id', $id)->first();
            $leadTime = null;
            
            if ($updatedWo->open_at && $updatedWo->closed_at) {
                $open = new \DateTime($updatedWo->open_at);
                $closed = new \DateTime($updatedWo->closed_at);
                $interval = $open->diff($closed);
                $leadTime = $interval->days * 24 + $interval->h + $interval->i / 60;
            }

            return response()->json([
                'success' => true,
                'message' => 'Progress berhasil diupdate',
                'data' => [
                    'lead_time' => $leadTime ? round($leadTime, 2) . ' jam' : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder updateProgress error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal update progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline data
     */
    public function getTimeline($id)
    {
        try {
            $wo = DB::table('trWorkOrder')
                ->select([
                    'trWorkOrder.*',
                    'creator.name as created_by_name',
                    'approver.name as approved_by_name',
                    'rejector.name as rejected_by_name',
                    'completer.name as completed_by_name'
                ])
                ->leftJoin('users as creator', 'trWorkOrder.created_by', '=', 'creator.id')
                ->leftJoin('users as approver', 'trWorkOrder.approved_by', '=', 'approver.id')
                ->leftJoin('users as rejector', 'trWorkOrder.rejected_by', '=', 'rejector.id')
                ->leftJoin('users as completer', 'trWorkOrder.completed_by', '=', 'completer.id')
                ->where('trWorkOrder.id', $id)
                ->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $wo
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder getTimeline error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil timeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created work order.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'nomor' => 'required|string|max:50|unique:trWorkOrder,nomor',
                'judul' => 'required|string|max:200',
                'deskripsi' => 'nullable|string',
                'dokumen' => 'required|file|mimes:pdf|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Upload file
            $file = $request->file('dokumen');
            $extension = $file->getClientOriginalExtension();
            $fileName = $request->nomor . '.' . $extension;
            
            $path = $file->storeAs('work-orders', $fileName, 'public');

            $id = DB::table('trWorkOrder')->insertGetId([
                'nomor' => $request->nomor,
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'file_path' => $path,
                'file_name' => $fileName,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil disimpan',
                'data' => [
                    'id' => $id,
                    'nomor' => $request->nomor
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder store error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit work order for approval (HANYA UNTUK USER)
     */
    public function submit($id)
    {
        try {
            $user = auth()->user();
            
            // Cek apakah user punya akses
            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Validasi: hanya user yang buat bisa submit
            if ($wo->created_by != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak mensubmit work order ini'
                ], 403);
            }

            // Validasi status
            if ($wo->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order dengan status draft yang bisa disubmit'
                ], 422);
            }

            DB::table('trWorkOrder')
                ->where('id', $id)
                ->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil disubmit'
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder submit error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve work order (HANYA UNTUK APPROVAL)
     */
    public function approve($id)
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval';

            // Validasi: hanya approval yang bisa approve
            if (!$isApproval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya approval yang bisa approve work order'
                ], 403);
            }

            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Validasi status
            if ($wo->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order dengan status submitted yang bisa diapprove'
                ], 422);
            }

            DB::table('trWorkOrder')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'progress_status' => 'open', // Set default ke OPEN saat approve
                    'open_at' => now(), // Timestamp open
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil diapprove dan siap untuk diproses'
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder approve error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject work order (HANYA UNTUK APPROVAL)
     */
    public function reject(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval';

            // Validasi: hanya approval yang bisa reject
            if (!$isApproval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya approval yang bisa reject work order'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'rejection_notes' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catatan penolakan harus diisi',
                    'errors' => $validator->errors()
                ], 422);
            }

            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Validasi status
            if ($wo->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order dengan status submitted yang bisa direject'
                ], 422);
            }

            DB::table('trWorkOrder')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                    'rejection_notes' => $request->rejection_notes,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil direject'
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder reject error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete work order (menyelesaikan work order secara keseluruhan)
     */
    public function complete($id)
    {
        try {
            $user = auth()->user();
            
            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Validasi status
            if ($wo->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order dengan status approved yang bisa diselesaikan'
                ], 422);
            }

            DB::table('trWorkOrder')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'completed_by' => auth()->id(),
                    'completed_at' => now(),
                    'progress_status' => 'closed', // Set progress ke closed juga
                    'closed_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil diselesaikan'
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder complete error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal complete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download work order document.
     */
    public function download($id)
    {
        try {
            $user = auth()->user();
            $isApproval = $user->role === 'approval';
            
            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo || !$wo->file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            // Validasi akses
            if (!$isApproval && $wo->created_by != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak mendownload file ini'
                ], 403);
            }

            if (!Storage::disk('public')->exists($wo->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File fisik tidak ditemukan'
                ], 404);
            }

            return Storage::disk('public')->download($wo->file_path, $wo->file_name);

        } catch (\Exception $e) {
            Log::error('WorkOrder download error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal download: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete work order (HANYA UNTUK USER yg punya)
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            $wo = DB::table('trWorkOrder')->where('id', $id)->first();

            if (!$wo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work Order tidak ditemukan'
                ], 404);
            }

            // Validasi: hanya user yang buat bisa hapus
            if ($wo->created_by != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak menghapus work order ini'
                ], 403);
            }

            // Only allow deletion of draft or rejected work orders
            if (!in_array($wo->status, ['draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya work order dengan status draft atau rejected yang bisa dihapus'
                ], 422);
            }

            // Hapus file
            if ($wo->file_path && Storage::disk('public')->exists($wo->file_path)) {
                Storage::disk('public')->delete($wo->file_path);
            }

            DB::table('trWorkOrder')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Work Order berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('WorkOrder destroy error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}