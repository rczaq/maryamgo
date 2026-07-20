<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    // State untuk modal & filter
    public $selectedTransactionId = null;
    public $showModalDetail = false;
    public $search = '';
    public $filterStatus = 'pending';
    public $catatanPenolakan = '';
    public $showModalBukti = false;

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    #[Computed]
    public function transaksiList()
    {
        return Transaksi::query()
            ->when($this->filterStatus !== 'semua', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('id', 'like', '%' . $this->search . '%')
                      ->orWhere('nama_pelanggan', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    #[Computed]
    public function countPending()
    {
        return Transaksi::where('status', 'pending')->count();
    }

    #[Computed]
    public function countSelesaiHariIni()
    {
        return Transaksi::where('status', 'disetujui')
            ->whereDate('updated_at', today())
            ->count();
    }

    #[Computed]
    public function transaksiTerpilih()
    {
        if (!$this->selectedTransactionId) {
            return null;
        }

        return Transaksi::with(['details.product'])->find($this->selectedTransactionId);
    }

    public function pilihTransaksi($id)
    {
        $this->selectedTransactionId = $id;
        $this->catatanPenolakan = '';
        $this->showModalBukti = false;
        $this->showModalDetail = true;
    }

    public function tutupModalDetail()
    {
        $this->showModalDetail = false;
        $this->selectedTransactionId = null;
        $this->catatanPenolakan = '';
        $this->showModalBukti = false;
    }

    public function setujui($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaksi = Transaksi::with('details.product')->lockForUpdate()->find($id);

                if (!$transaksi) {
                    throw new \Exception("Transaksi #{$id} tidak ditemukan.");
                }

                if ($transaksi->status === 'disetujui') {
                    throw new \Exception("Transaksi ini sudah disetujui sebelumnya.");
                }

                foreach ($transaksi->details as $detail) {
                    if (!$detail->product) {
                        throw new \Exception("Data produk ID ({$detail->product_id}) tidak ditemukan di master data.");
                    }

                    if ($detail->product->stok < $detail->jumlah) {
                        throw new \Exception("Stok produk '{$detail->product->nama_produk}' kurang. (Sisa: {$detail->product->stok}, Dibeli: {$detail->jumlah})");
                    }
                }

                foreach ($transaksi->details as $detail) {
                    $detail->product->decrement('stok', $detail->jumlah);
                }

                $transaksi->update([
                    'status' => 'disetujui',
                ]);
            });

            $this->tutupModalDetail();
            session()->flash('success', "Transaksi #{$id} berhasil disetujui & stok dipotong!");

        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function tolak($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transaksi = Transaksi::find($id);
                if (!$transaksi) {
                    throw new \Exception("Transaksi tidak ditemukan.");
                }

                $transaksi->update([
                    'status' => 'ditolak',
                ]);
            });

            $this->tutupModalDetail();
            session()->flash('error', "Transaksi #{$id} ditolak.");

        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function toggleModalBukti()
    {
        $this->showModalBukti = !$this->showModalBukti;
    }
};
?>

<!-- Menambahkan wire:poll.5s untuk auto-update data setiap 5 detik -->
<div wire:poll.5s class="space-y-6">

    <!-- Flash Notification -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg text-xs font-semibold flex items-center justify-between shadow-sm">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 rounded-r-lg text-xs font-semibold flex items-center justify-between shadow-sm">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-700">&times;</button>
        </div>
    @endif

    <!-- Counter Cards -->
    <div class="flex flex-col sm:flex-row gap-4 w-full select-none">
        <div class="flex-1 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-xl">pending_actions</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menunggu Pemeriksaan</p>
                <h4 class="text-xl font-black text-slate-800 mt-0.5">{{ $this->countPending }} Transaksi</h4>
            </div>
        </div>

        <div class="flex-1 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-xl">task_alt</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Disetujui Hari Ini</p>
                <h4 class="text-xl font-black text-slate-800 mt-0.5">{{ $this->countSelesaiHariIni }} Transaksi</h4>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <div class="flex gap-1 bg-slate-100 p-1 rounded-lg text-xs font-semibold w-full md:w-auto">
            <button type="button" wire:click="$set('filterStatus', 'pending')" 
                    class="px-3 py-1.5 rounded-md transition-colors flex-1 md:flex-none text-center {{ $filterStatus === 'pending' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-500' }}">
                Pending ({{ $this->countPending }})
            </button>
            <button type="button" wire:click="$set('filterStatus', 'disetujui')" 
                    class="px-3 py-1.5 rounded-md transition-colors flex-1 md:flex-none text-center {{ $filterStatus === 'disetujui' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-500' }}">
                Disetujui
            </button>
            <button type="button" wire:click="$set('filterStatus', 'ditolak')" 
                    class="px-3 py-1.5 rounded-md transition-colors flex-1 md:flex-none text-center {{ $filterStatus === 'ditolak' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-500' }}">
                Ditolak
            </button>
            <button type="button" wire:click="$set('filterStatus', 'semua')" 
                    class="px-3 py-1.5 rounded-md transition-colors flex-1 md:flex-none text-center {{ $filterStatus === 'semua' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-500' }}">
                Semua
            </button>
        </div>

        <div class="relative w-full md:w-64">
            <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="Cari ID / Pelanggan..." 
                   class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
    </div>

    <!-- TABEL TRANSAKSI -->
    <div class="w-full bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50 flex justify-between items-center select-none">
            <span class="font-bold text-xs text-slate-700 uppercase tracking-wider">
                Daftar Transaksi ({{ strtoupper($filterStatus) }})
            </span>
            <span class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold uppercase">
                Total: {{ $this->transaksiList->total() }}
            </span>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider select-none">
                        <th class="px-5 py-3">ID / Waktu</th>
                        <th class="px-5 py-3">Nama Pelanggan</th>
                        <th class="px-5 py-3">Metode & Status</th>
                        <th class="px-5 py-3 text-right">Total Tagihan</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse($this->transaksiList as $trx)
                        <tr wire:key="trx-row-{{ $trx->id }}" class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">#{{ $trx->id }}</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">
                                    {{ $trx->created_at->format('d/m/Y H:i') }} WIB
                                </span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">
                                {{ $trx->nama_pelanggan }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-600 font-medium">{{ $trx->metode_pembayaran }}</span>
                                    @if($trx->status === 'pending')
                                        <span class="text-[9px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold">Pending</span>
                                    @elseif($trx->status === 'disetujui')
                                        <span class="text-[9px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">Selesai</span>
                                    @else
                                        <span class="text-[9px] bg-rose-100 text-rose-800 px-2 py-0.5 rounded font-bold">Ditolak</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900">
                                Rp {{ number_format($trx->total_harga, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button"
                                        wire:click="pilihTransaksi('{{ $trx->id }}')"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg font-semibold text-xs transition-all shadow-xs cursor-pointer whitespace-nowrap disabled:opacity-50">
                                    
                                    <span wire:loading wire:target="pilihTransaksi('{{ $trx->id }}')" class="animate-spin material-symbols-outlined text-[16px]">progress_activity</span>
                                    <span wire:loading.remove wire:target="pilihTransaksi('{{ $trx->id }}')" class="material-symbols-outlined text-[16px]">visibility</span>
                                    
                                    Periksa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">receipt_long</span>
                                <p class="text-xs font-medium">Tidak ada transaksi ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $this->transaksiList->links() }}
        </div>
    </div>

    <!-- POPUP MODAL VERIFIKASI -->
    @if($showModalDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
            
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden my-8">
                
                <!-- Header -->
                <div class="px-5 py-3.5 bg-slate-800 text-white flex justify-between items-center select-none">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Verifikasi Struk</span>
                        <h3 class="font-bold text-sm mt-0.5">Transaksi #{{ $selectedTransactionId }}</h3>
                    </div>
                    <button type="button" wire:click="tutupModalDetail" class="text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                    @if($this->transaksiTerpilih)
                        <!-- Rincian Produk -->
                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 select-none">1. Rincian Produk</h4>
                            <div class="bg-slate-50 rounded-lg p-3 space-y-2 border border-slate-200">
                                @foreach($this->transaksiTerpilih->details as $detail)
                                    <div class="flex justify-between text-xs gap-4">
                                        <span class="text-slate-700 font-medium truncate">
                                            {{ $detail->product->nama_produk ?? 'Produk Dihapus' }} 
                                            <span class="text-slate-400 font-bold ml-1">x{{ $detail->jumlah }}</span>
                                        </span>
                                        <span class="font-semibold text-slate-900 flex-shrink-0">
                                            Rp {{ number_format($detail->harga_satuan * $detail->jumlah, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="h-px bg-slate-200 my-1"></div>
                                <div class="flex justify-between text-xs font-bold text-slate-900">
                                    <span>Total Tagihan</span>
                                    <span class="text-emerald-600 text-sm">Rp {{ number_format($this->transaksiTerpilih->total_harga, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Transfer & Bukti -->
                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 select-none">2. Bukti Transfer</h4>
                            <div class="border border-slate-200 rounded-lg p-3 space-y-2 bg-slate-50">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Nama Pelanggan:</span>
                                    <span class="font-semibold text-slate-800">{{ $this->transaksiTerpilih->nama_pelanggan }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Metode Pembayaran:</span>
                                    <span class="font-semibold text-slate-800">{{ $this->transaksiTerpilih->metode_pembayaran }}</span>
                                </div>
                                
                                <div class="mt-2 pt-2 border-t border-slate-200">
                                    @if($this->transaksiTerpilih->bukti_pembayaran)
                                        <div class="relative overflow-hidden rounded-lg border border-slate-300 max-h-56 bg-slate-900 group cursor-pointer"
                                             wire:click="toggleModalBukti">
                                            <img src="{{ asset('storage/' . $this->transaksiTerpilih->bukti_pembayaran) }}" 
                                                 alt="Bukti Transfer" 
                                                 class="w-full h-auto object-contain mx-auto">
                                        </div>
                                    @else
                                        <div class="w-full h-20 bg-slate-100 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
                                            <span class="text-[10px] font-bold">Tidak Ada Bukti Lampiran</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($this->transaksiTerpilih->status === 'pending')
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Catatan Penolakan (Opsional)</label>
                                <input type="text" wire:model="catatanPenolakan" placeholder="Alasan penolakan..." class="w-full p-2 border border-slate-200 rounded-lg text-xs outline-none">
                            </div>
                        @endif
                    @else
                        <div class="py-12 text-center text-slate-400">
                            <span class="animate-spin material-symbols-outlined text-3xl text-emerald-600 block mb-2">progress_activity</span>
                            <p class="text-xs font-medium">Memuat data transaksi...</p>
                        </div>
                    @endif
                </div>

                <!-- Footer / Tombol Aksi -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex gap-2">
                    @if($this->transaksiTerpilih && $this->transaksiTerpilih->status === 'pending')
                        <button type="button" wire:click="tolak('{{ $this->transaksiTerpilih->id }}')" class="flex-1 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white text-xs font-bold py-2.5 rounded-lg border border-rose-200 transition-colors">
                            Tolak
                        </button>
                        <button type="button" wire:click="setujui('{{ $this->transaksiTerpilih->id }}')" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-lg transition-colors">
                            Setujui & Selesai
                        </button>
                    @else
                        <button type="button" wire:click="tutupModalDetail" class="w-full bg-slate-200 text-slate-800 text-xs font-bold py-2.5 rounded-lg">
                            Tutup
                        </button>
                    @endif
                </div>

            </div>
        </div>
    @endif

</div>