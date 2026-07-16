<?php

use Livewire\Component;
use App\Models\Transaksi; // Sesuaikan dengan nama Model Transaksi-mu
use App\Models\TransaksiDetail; // Sesuaikan dengan nama Model Detail Transaksi-mu

new class extends Component
{
    // State untuk menyimpan ID transaksi yang sedang diperiksa admin
    public $selectedTransactionId = null;

    // Ambil daftar antrean transaksi yang statusnya masih 'pending'
    public function getTransaksiPendingProperty()
    {
        return Transaksi::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Hitung jumlah transaksi yang butuh tindakan segera
    public function getCountPendingProperty()
    {
        return Transaksi::where('status', 'pending')->count();
    }

    // Hitung transaksi yang sukses disetujui hari ini saja
    public function getCountSelesaiProperty()
    {
        return Transaksi::where('status', 'disetujui')
            ->whereDate('updated_at', today())
            ->count();
    }

    // Ambil data detail lengkap dari transaksi yang diklik admin
    public function getTransaksiTerpilihProperty()
    {
        if (!$this->selectedTransactionId) {
            return null;
        }
        
        // Eager load relasi detail transaksi dan produk terkait
        return Transaksi::with(['details.product'])->find($this->selectedTransactionId);
    }

    // Fungsi ketika admin mengklik tombol "Periksa"
    public function pilihTransaksi($id)
    {
        $this->selectedTransactionId = $id;
    }

    // Aksi untuk menyetujui transaksi
    public function setujui($id)
    {
        $transaksi = Transaksi::find($id);
        
        if ($transaksi) {
            $transaksi->update(['status' => 'disetujui']);
            
            // OPTIONAL: Otomatis kurangi stok produk terdaftar
            foreach ($transaksi->details as $detail) {
                if ($detail->product) {
                    $detail->product->decrement('stok', $detail->jumlah);
                }
            }

            $this->selectedTransactionId = null;
            session()->flash('success', "Transaksi #{$id} berhasil disetujui & stok otomatis dipotong!");
        }
    }

    // Aksi untuk menolak transaksi (misal struk palsu / nominal tidak pas)
    public function tolak($id)
    {
        $transaksi = Transaksi::find($id);
        
        if ($transaksi) {
            $transaksi->update(['status' => 'ditolak']);
            $this->selectedTransactionId = null;
            session()->flash('error', "Transaksi #{$id} telah ditolak sistem.");
        }
    }
};
?>

<div class="space-y-6">
    <!-- Notifikasi Sukses / Gagal -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg text-xs font-semibold flex items-center gap-2 shadow-sm">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 rounded-r-lg text-xs font-semibold flex items-center gap-2 shadow-sm">
            <span class="material-symbols-outlined text-base">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Mini Counter Cards -->
    <div class="flex flex-col sm:flex-row gap-4 w-full select-none">
        <div class="flex-1 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-lg">pending_actions</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menunggu Diperiksa</p>
                <h4 class="text-lg font-black text-slate-800 mt-0.5">{{ $this->countPending }} Transaksi</h4>
            </div>
        </div>
        <div class="flex-1 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-lg">task_alt</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Selesai Hari Ini</p>
                <h4 class="text-lg font-black text-slate-800 mt-0.5">{{ $this->countSelesai }} Transaksi</h4>
            </div>
        </div>
    </div>

    <!-- Split Workspace Layout -->
    <div class="flex flex-col lg:flex-row gap-6 items-start">
        
        <!-- SISI KIRI: DAFTAR ANTRIAN TRANSAKSI -->
        <div class="flex-1 w-full bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50 flex justify-between items-center select-none">
                <span class="font-bold text-xs text-slate-700 uppercase tracking-wider">Antrean Validasi</span>
                <span class="text-[10px] bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded font-bold uppercase">Butuh Tindakan</span>
            </div>

            <div class="w-full overflow-x-auto">
                <div class="min-w-[600px]">
                    <div class="grid grid-cols-12 bg-slate-100 px-5 py-2.5 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider select-none">
                        <div class="col-span-3">ID / Waktu</div>
                        <div class="col-span-4">Pelanggan</div>
                        <div class="col-span-3 text-right">Total Tagihan</div>
                        <div class="col-span-2 text-center">Aksi</div>
                    </div>

                    <div class="divide-y divide-slate-100 bg-white">
                        @forelse($this->transaksiPending as $trx)
                            <div class="grid grid-cols-12 px-5 py-3.5 items-center hover:bg-slate-50/80 transition-colors cursor-pointer {{ $this->selectedTransactionId === $trx->id ? 'bg-emerald-50/50' : '' }}"
                                 wire:click="pilihTransaksi('{{ $trx->id }}')"
                                 wire:key="trx-row-{{ $trx->id }}">
                                <div class="col-span-3">
                                    <span class="font-bold text-xs text-slate-800 block">#{{ $trx->id }}</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ $trx->created_at->format('H:i') }} WIB</span>
                                </div>
                                <div class="col-span-4">
                                    <span class="font-semibold text-xs text-slate-700 block truncate">{{ $trx->nama_pelanggan }}</span>
                                    <span class="text-[10px] text-slate-400 block truncate">{{ $trx->metode_pembayaran }}</span>
                                </div>
                                <div class="col-span-3 text-right font-bold text-xs text-slate-900">
                                    Rp {{ number_format($trx->total_harga, 0, ',', '.') }}
                                </div>
                                <div class="col-span-2 text-center">
                                    <button class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-2.5 py-1 rounded text-[11px] font-bold transition-colors">
                                        Periksa
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center text-slate-400" wire:key="trx-empty">
                                <span class="material-symbols-outlined text-3xl text-slate-300 block mb-1">receipt_long</span>
                                <p class="text-xs font-medium">Belum ada antrean transaksi masuk.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: WORKSPACE PEMERIKSAAN DETAIL STRUK -->
        <div class="w-full lg:w-96 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden sticky top-6">
            @if($this->transaksiTerpilih)
                <!-- Header Workspace Detail -->
                <div class="px-5 py-3.5 bg-slate-800 text-white flex justify-between items-center select-none" wire:key="workspace-header">
                    <div>
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Validasi Dokumen</span>
                        <h3 class="font-bold text-xs mt-0.5">#{{ $this->transaksiTerpilih->id }}</h3>
                    </div>
                    <button wire:click="$set('selectedTransactionId', null)" class="text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>

                <!-- Konten Pembayaran -->
                <div class="p-5 space-y-4 max-h-[60vh] overflow-y-auto" wire:key="workspace-body-{{ $this->transaksiTerpilih->id }}">
                    <!-- List Item Belanja -->
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 select-none">1. Item Yang Dibeli</h4>
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
                                <span class="select-none">Total Pesanan</span>
                                <span class="text-emerald-600">Rp {{ number_format($this->transaksiTerpilih->total_harga, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info & Bukti Pembayaran -->
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 select-none">2. Bukti & Info Pembayaran</h4>
                        <div class="border border-slate-200 rounded-lg p-3 space-y-2 bg-slate-50">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-505 select-none">Metode:</span>
                                <span class="font-semibold text-slate-800">{{ $this->transaksiTerpilih->metode_pembayaran }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-500 select-none">Atas Nama:</span>
                                <span class="font-semibold text-slate-800">{{ $this->transaksiTerpilih->atas_nama ?? '-' }}</span>
                            </div>
                            
                            <!-- Tampilan Gambar Struk Upload -->
                            <div class="mt-2 pt-2 border-t border-slate-200">
                                <p class="text-[10px] text-slate-400 font-medium mb-1 select-none">Lampiran Struk Transfer:</p>
                                @if($this->transaksiTerpilih->bukti_pembayaran)
                                    <a href="{{ asset('storage/' . $this->transaksiTerpilih->bukti_pembayaran) }}" target="_blank" class="block group">
                                        <div class="relative overflow-hidden rounded-lg border border-slate-300 max-h-48 bg-slate-200">
                                            <img src="{{ asset('storage/' . $this->transaksiTerpilih->bukti_pembayaran) }}" 
                                                 alt="Bukti Transfer" 
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold gap-1">
                                                <span class="material-symbols-outlined text-sm">open_in_new</span> Buka Gambar Penuh
                                            </div>
                                        </div>
                                    </a>
                                @else
                                    <div class="w-full h-24 bg-slate-100 rounded-lg border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 select-none">
                                        <span class="material-symbols-outlined text-xl">no_photography</span>
                                        <span class="text-[10px] font-bold mt-1">Tidak Ada Bukti Lampiran</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Instruksi Validasi -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex gap-2 items-start select-none">
                        <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">info</span>
                        <p class="text-[10px] text-amber-800 leading-relaxed">
                            Pastikan nominal akhir pada struk senilai **Rp {{ number_format($this->transaksiTerpilih->total_harga, 0, ',', '.') }}** dan dana sudah masuk ke mutasi rekening bank kasir.
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi validasi -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex gap-2" wire:key="workspace-footer">
                    <button type="button" 
                            wire:click="tolak('{{ $this->transaksiTerpilih->id }}')"
                            class="flex-1 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white text-xs font-bold py-2.5 rounded-lg border border-rose-200 transition-colors text-center cursor-pointer">
                        Tolak / Salah
                    </button>
                    <button type="button" 
                            wire:click="setujui('{{ $this->transaksiTerpilih->id }}')"
                            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-lg shadow-sm transition-colors text-center cursor-pointer">
                        Setujui & Proses
                    </button>
                </div>
            @else
                <!-- Tampilan Kosong Ketika Belum Memilih Transaksi -->
                <div class="p-12 text-center text-slate-400 select-none" wire:key="workspace-empty">
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">touch_app</span>
                    <p class="text-xs font-bold text-slate-500">Pilih Transaksi</p>
                    <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">Klik tombol "Periksa" pada baris antrean transaksi untuk memvalidasi bukti pembayaran pelanggan.</p>
                </div>
            @endif
        </div>

    </div>
</div>