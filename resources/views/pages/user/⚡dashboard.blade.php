<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination, WithFileUploads;

    // State Filter (Hanya Pencarian)
    public $search = '';

    // State Keranjang Belanja
    public $cart = []; 

    // State Form Pemesanan
    public $nama_pelanggan = '';
    public $metode_pembayaran = 'QRIS';
    public $atas_nama = '';
    public $bukti_pembayaran = null;
    
    // State Modal
    public $showQrisModal = false;
    public $showCartModal = false;
    public $lastTransaction = null;

    // State Modal Detail Produk
    public $showProductModal = false;
    public $selectedProduct = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }

    public function toggleCartModal($status = null)
    {
        $this->showCartModal = is_null($status) ? !$this->showCartModal : $status;
    }

    public function openProductModal($productId)
    {
        $this->selectedProduct = Produk::find($productId);
        if ($this->selectedProduct) {
            $this->showProductModal = true;
        }
    }

    public function closeProductModal()
    {
        $this->showProductModal = false;
        $this->selectedProduct = null;
    }

    public function toggleQrisModal($status = true)
    {
        $this->showQrisModal = $status;
    }

    public function getQrisFotoProperty()
    {
        $qrisPath = DB::table('settings')->value('foto');

        if ($qrisPath) {
            return asset('storage/' . $qrisPath);
        }

        return asset('images/qris.jpg');
    }

    public function updatedMetodePembayaran($value)
    {
        if ($value !== 'QRIS') {
            $this->bukti_pembayaran = null;
            $this->resetValidation('bukti_pembayaran');
        }
    }

    public function removeBukti()
    {
        $this->bukti_pembayaran = null;
        $this->resetValidation('bukti_pembayaran');
    }

    public function addToCart($productId)
    {
        $produk = Produk::find($productId);

        if (!$produk || $produk->stok <= 0) {
            $this->dispatch('show-toast', message: 'Stok produk habis!', type: 'error');
            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['jumlah'] < $produk->stok) {
                $this->cart[$productId]['jumlah']++;
            } else {
                $this->dispatch('show-toast', message: 'Mencapai batas stok!', type: 'error');
                return;
            }
        } else {
            $this->cart[$productId] = [
                'id' => $produk->id,
                'nama' => $produk->nama_produk,
                'harga' => $produk->harga,
                'jumlah' => 1,
                'stok_maksimal' => $produk->stok
            ];
        }

        $this->dispatch('show-toast', message: 'Produk masuk keranjang!', type: 'success');
    }

    public function updateQuantity($productId, $delta)
    {
        if (isset($this->cart[$productId])) {
            $newQty = $this->cart[$productId]['jumlah'] + $delta;
            if ($newQty > 0 && $newQty <= $this->cart[$productId]['stok_maksimal']) {
                $this->cart[$productId]['jumlah'] = $newQty;
            } elseif ($newQty <= 0) {
                $this->removeFromCart($productId);
            }
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
    }

    public function getTotalItemsProperty()
    {
        return array_sum(array_column($this->cart, 'jumlah'));
    }

    public function getTotalHargaProperty()
    {
        return array_reduce($this->cart, function ($total, $item) {
            return $total + ($item['harga'] * $item['jumlah']);
        }, 0);
    }

    public function submitOrder()
    {
        $rules = [
            'nama_pelanggan' => 'required|min:3|string',
            'metode_pembayaran' => 'required|string',
            'atas_nama' => 'nullable|string',
        ];

        if ($this->metode_pembayaran === 'QRIS') {
            $rules['bukti_pembayaran'] = 'required|image|max:2048';
        } else {
            $rules['bukti_pembayaran'] = 'nullable|image|max:2048';
        }

        $this->validate($rules, [
            'nama_pelanggan.required' => 'Silakan isi nama pemesan!',
            'nama_pelanggan.min' => 'Nama minimal 3 huruf.',
            'bukti_pembayaran.required' => 'Wajib mengunggah foto bukti pembayaran QRIS!',
            'bukti_pembayaran.image' => 'File harus berupa foto/gambar (JPG, PNG).',
            'bukti_pembayaran.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        if (empty($this->cart)) {
            $this->dispatch('show-toast', message: 'Keranjang belanja masih kosong!', type: 'error');
            return;
        }

        $trxId = 'TRX-' . strtoupper(Str::random(5));

        try {
            $buktiPath = null;
            if ($this->metode_pembayaran === 'QRIS' && $this->bukti_pembayaran) {
                $buktiPath = $this->bukti_pembayaran->store('bukti-transaksi', 'public');
            }

            DB::transaction(function () use ($trxId, $buktiPath) {
                DB::table('transaksis')->insert([
                    'id' => $trxId,
                    'nama_pelanggan' => $this->nama_pelanggan,
                    'metode_pembayaran' => $this->metode_pembayaran,
                    'atas_nama' => $this->atas_nama ?: $this->nama_pelanggan,
                    'total_harga' => $this->totalHarga,
                    'bukti_pembayaran' => $buktiPath,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($this->cart as $item) {
                    DB::table('transaksi_detail')->insert([
                        'transaksis_id' => $trxId,
                        'product_id' => $item['id'],
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $item['harga'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('produks')->where('id', $item['id'])->decrement('stok', $item['jumlah']);
                }
            });

            // Simpan detail untuk Nota yang bisa di-download
            $this->lastTransaction = [
                'id' => $trxId,
                'nama' => $this->nama_pelanggan,
                'total' => $this->totalHarga,
                'metode' => $this->metode_pembayaran,
                'items' => array_values($this->cart),
                'tanggal' => now()->format('d/m/Y H:i')
            ];

            // Reset Form & Keranjang
            $this->cart = [];
            $this->nama_pelanggan = '';
            $this->atas_nama = '';
            $this->bukti_pembayaran = null;
            $this->showCartModal = false;

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Gagal membuat pesanan: ' . $e->getMessage(), type: 'error');
        }
    }

    public function with(): array
    {
        $query = Produk::query();

        if (!empty($this->search)) {
            $query->where('nama_produk', 'like', '%' . $this->search . '%')
                  ->orWhere('kode_produk', 'like', '%' . $this->search . '%');
        }

        return [
            'produks' => $query->latest()->paginate(8)
        ];
    }
};
?>

<div class="min-h-screen bg-[#FDFBF7] text-[#2A1810]">

    <!-- ASSETS (Tailwind, Icons, & HTML2Canvas untuk Download Gambar) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
    </style>

    <!-- HEADER -->
    <header class="bg-white border-b border-[#EFE6DC] sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#8C4A27] text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-2xl">bakery_dining</span>
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-[#2A1810]">Maryam <span class="text-[#8C4A27]">Go</span></span>
                    <span class="block text-[10px] text-[#8C7462] font-semibold -mt-1 uppercase tracking-wider">Pemesanan Langsung</span>
                </div>
            </div>

            <button wire:click="toggleCartModal(true)" 
                    class="relative flex items-center gap-2 bg-[#F5EDE4] hover:bg-[#EFE6DC] text-[#8C4A27] px-4 py-2 rounded-xl border border-[#EFE6DC] font-bold text-xs cursor-pointer transition-colors">
                <span class="material-symbols-outlined text-xl">shopping_bag</span>
                <span class="hidden sm:inline">Keranjang</span>
                @if($this->totalItems > 0)
                    <span class="bg-[#8C4A27] text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-extrabold">
                        {{ $this->totalItems }}
                    </span>
                @endif
            </button>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- BAR PENCARIAN -->
        <div class="mb-8">
            <div class="relative w-full max-w-md mx-auto">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-[#A39181]">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari menu roti..." 
                       class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-[#EFE6DC] text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#8C4A27] shadow-xs">
            </div>
        </div>

        <!-- DAFTAR PRODUK -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @forelse($produks as $produk)
                <div class="bg-white rounded-2xl border border-[#EFE6DC] overflow-hidden shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between">
                    
                    <div wire:click="openProductModal({{ $produk->id }})" class="cursor-pointer group">
                        <div class="h-44 bg-[#F5EDE4] relative flex items-center justify-center overflow-hidden">
                            @if($produk->foto && file_exists(public_path('storage/' . $produk->foto)))
                                <img src="{{ asset('storage/' . $produk->foto) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <span class="material-symbols-outlined text-4xl text-[#A39181]">bakery_dining</span>
                            @endif
                            
                            <span class="absolute top-2.5 left-2.5 text-[10px] font-bold px-2 py-0.5 rounded-full {{ $produk->stok > 0 ? 'bg-emerald-800 text-white' : 'bg-rose-800 text-white' }}">
                                {{ $produk->stok > 0 ? 'Stok: '.$produk->stok : 'Habis' }}
                            </span>

                            <span class="absolute bottom-2.5 right-2.5 bg-black/60 text-white text-[10px] font-bold px-2 py-1 rounded-lg backdrop-blur-xs flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-xs">visibility</span> Detail
                            </span>
                        </div>

                        <div class="p-4">
                            <h3 class="font-bold text-sm text-[#2A1810] line-clamp-1 group-hover:text-[#8C4A27] transition-colors">{{ $produk->nama_produk }}</h3>
                            <p class="text-xs text-[#786455] line-clamp-2 mt-1 mb-2">{{ $produk->deskripsi ?? 'Roti Maryam lezat renyah diluar lembut didalam.' }}</p>
                            <span class="font-mono font-bold text-sm text-[#8C4A27]">Rp {{ number_format($produk->harga, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="p-4 pt-0">
                        @if($produk->stok > 0)
                            <button wire:click="addToCart({{ $produk->id }})" class="w-full py-2 rounded-xl bg-[#8C4A27] hover:bg-[#703A1E] text-white text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer transition-colors">
                                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                                <span>+ Tambah Pesanan</span>
                            </button>
                        @else
                            <button disabled class="w-full py-2 rounded-xl bg-[#EFE6DC] text-[#A39181] text-xs font-bold cursor-not-allowed">Stok Habis</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-[#EFE6DC]">
                    <span class="material-symbols-outlined text-4xl text-[#A39181] mb-2 block">search_off</span>
                    <p class="text-xs font-bold text-[#786455]">Produk tidak ditemukan.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $produks->links() }}</div>
    </main>

    <!-- TOMBOL FLOATING KERANJANG -->
    @if($this->totalItems > 0)
        <button wire:click="toggleCartModal(true)" 
                class="fixed bottom-6 right-6 z-40 bg-[#8C4A27] hover:bg-[#703A1E] text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 transition-all cursor-pointer border border-white/20 hover:scale-105">
            <div class="relative">
                <span class="material-symbols-outlined text-2xl">shopping_bag</span>
                <span class="absolute -top-2 -right-2 bg-rose-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center font-mono border-2 border-[#8C4A27]">
                    {{ $this->totalItems }}
                </span>
            </div>
            <div class="text-left">
                <span class="block text-[10px] text-white/80 uppercase font-semibold">Total Pesanan</span>
                <span class="font-mono font-extrabold text-sm">Rp {{ number_format($this->totalHarga, 0, ',', '.') }}</span>
            </div>
            <span class="material-symbols-outlined text-lg ml-1">chevron_right</span>
        </button>
    @endif

    <!-- POPUP MODAL DRAWER KERANJANG BELANJA -->
    @if($showCartModal)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex justify-end transition-opacity"
             wire:click.self="toggleCartModal(false)">
            
            <div class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col justify-between overflow-y-auto p-6 relative border-l border-[#EFE6DC]">
                <div>
                    <div class="flex items-center justify-between border-b border-[#EFE6DC] pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#8C4A27] text-2xl">shopping_bag</span>
                            <h2 class="font-extrabold text-base text-[#2A1810]">Keranjang Pesanan</h2>
                        </div>
                        <button type="button" wire:click="toggleCartModal(false)" class="text-[#A39181] hover:text-[#2A1810] p-1 rounded-lg hover:bg-[#F5EDE4] transition-colors cursor-pointer">
                            <span class="material-symbols-outlined block">close</span>
                        </button>
                    </div>

                    @if(empty($cart))
                        <div class="text-center py-12 border-2 border-dashed border-[#EFE6DC] rounded-2xl my-6">
                            <span class="material-symbols-outlined text-5xl text-[#A39181] mb-2 block">remove_shopping_cart</span>
                            <p class="text-xs text-[#786455] font-bold">Keranjang masih kosong.</p>
                            <p class="text-[11px] text-[#A39181] mt-1">Pilih menu roti favorit Anda untuk memulai pesanan.</p>
                        </div>
                    @else
                        <div class="space-y-3 mb-6 max-h-60 overflow-y-auto pr-1">
                            @foreach($cart as $item)
                                <div class="flex justify-between items-center p-3 rounded-xl bg-[#F5EDE4] text-xs border border-[#EFE6DC]">
                                    <div class="flex-1 pr-2">
                                        <p class="font-bold text-[#2A1810] line-clamp-1">{{ $item['nama'] }}</p>
                                        <p class="text-[#8C4A27] font-mono font-bold">Rp {{ number_format($item['harga'], 0, ',', '.') }}</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 bg-white px-2 py-1 rounded-lg border border-[#EFE6DC]">
                                        <button wire:click="updateQuantity({{ $item['id'] }}, -1)" class="text-[#2A1810] font-bold px-1.5 hover:bg-[#F5EDE4] rounded cursor-pointer">-</button>
                                        <span class="font-bold font-mono text-xs px-1">{{ $item['jumlah'] }}</span>
                                        <button wire:click="updateQuantity({{ $item['id'] }}, 1)" class="text-[#2A1810] font-bold px-1.5 hover:bg-[#F5EDE4] rounded cursor-pointer">+</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($cart))
                        <div class="space-y-4 border-t border-[#EFE6DC] pt-4">
                            <div>
                                <label class="block text-[11px] font-bold text-[#2A1810] mb-1">Atas Nama Pemesan *</label>
                                <input type="text" wire:model="nama_pelanggan" placeholder="Masukkan nama Anda..." 
                                       class="w-full px-3.5 py-2 rounded-xl bg-[#FDFBF7] border border-[#EFE6DC] text-xs focus:ring-2 focus:ring-[#8C4A27] focus:outline-none">
                                @error('nama_pelanggan') <span class="text-rose-600 text-[10px] font-semibold block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-[#2A1810] mb-1">Metode Pembayaran *</label>
                                <select wire:model.live="metode_pembayaran" class="w-full px-3.5 py-2 rounded-xl bg-[#FDFBF7] border border-[#EFE6DC] text-xs focus:ring-2 focus:ring-[#8C4A27] focus:outline-none cursor-pointer">
                                    <option value="QRIS">QRIS / E-Wallet</option>
                                    <option value="Bayar di Kasir">Bayar Tunai di Kasir</option>
                                </select>
                            </div>

                            @if($metode_pembayaran === 'QRIS')
                                <div class="bg-[#F5EDE4] p-4 rounded-xl border border-[#EFE6DC] text-center space-y-3">
                                    <div class="flex items-center justify-between border-b border-[#EFE6DC]/80 pb-2">
                                        <span class="text-[11px] font-bold text-[#2A1810] flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm text-[#8C4A27]">qr_code_scanner</span>
                                            Scan QRIS Pembayaran
                                        </span>
                                        <span class="text-[9px] font-extrabold bg-[#8C4A27] text-white px-2 py-0.5 rounded-md">ALL PAYMENT</span>
                                    </div>

                                    <div class="bg-white p-3 rounded-xl border border-[#EFE6DC] inline-block shadow-sm">
                                        <img src="{{ $this->qrisFoto }}" alt="QRIS Maryam Go" 
                                             wire:click="toggleQrisModal(true)"
                                             class="w-40 h-40 object-contain mx-auto rounded-lg cursor-pointer hover:opacity-90 transition-opacity" 
                                             title="Klik untuk memperbesar">

                                        <div class="flex items-center justify-center gap-2 mt-2 pt-2 border-t border-[#EFE6DC]">
                                            <button type="button" wire:click="toggleQrisModal(true)" 
                                                    class="px-2.5 py-1 rounded-lg bg-[#F5EDE4] hover:bg-[#EFE6DC] text-[#8C4A27] text-[10px] font-bold flex items-center gap-1 cursor-pointer transition-colors">
                                                <span class="material-symbols-outlined text-xs">zoom_in</span>
                                                <span>Perbesar</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="bg-white p-2.5 rounded-xl border border-[#EFE6DC] flex items-center justify-between">
                                        <div class="text-left">
                                            <span class="block text-[9px] text-[#8C7462] font-semibold uppercase">Nominal Transfer</span>
                                            <span class="font-mono font-extrabold text-xs text-[#8C4A27]">Rp {{ number_format($this->totalHarga, 0, ',', '.') }}</span>
                                        </div>
                                        <button type="button" 
                                                onclick="navigator.clipboard.writeText('{{ $this->totalHarga }}'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Nominal berhasil disalin!', type: 'success' } }))"
                                                class="px-2.5 py-1 rounded-lg bg-[#F5EDE4] hover:bg-[#EFE6DC] text-[#8C4A27] text-[10px] font-bold flex items-center gap-1 cursor-pointer">
                                            <span class="material-symbols-outlined text-xs">content_copy</span>
                                            <span>Salin</span>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-[#2A1810] mb-1">Atas Nama Rekening/E-Wallet Pengirim (Opsional)</label>
                                    <input type="text" wire:model="atas_nama" placeholder="Misal: Budi Santoso" 
                                           class="w-full px-3.5 py-2 rounded-xl bg-[#FDFBF7] border border-[#EFE6DC] text-xs focus:ring-2 focus:ring-[#8C4A27] focus:outline-none">
                                </div>

                                <div class="bg-[#F5EDE4]/60 p-3 rounded-xl border border-[#EFE6DC]">
                                    <label class="block text-[11px] font-bold text-[#2A1810] mb-1">Upload Bukti Transfer QRIS *</label>
                                    <input type="file" wire:model="bukti_pembayaran" accept="image/*"
                                           class="w-full text-xs text-[#786455] file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-[#8C4A27] file:text-white hover:file:bg-[#703A1E] cursor-pointer">
                                    
                                    <div wire:loading wire:target="bukti_pembayaran" class="text-[10px] text-[#8C4A27] font-semibold mt-1.5 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs animate-spin">sync</span>
                                        <span>Mengunggah foto...</span>
                                    </div>

                                    @if ($bukti_pembayaran)
                                        <div class="mt-2.5 flex items-center justify-between bg-white p-2 rounded-lg border border-[#EFE6DC]">
                                            <div class="flex items-center gap-2.5 overflow-hidden">
                                                <img src="{{ $bukti_pembayaran->temporaryUrl() }}" class="w-12 h-12 object-cover rounded-md border border-[#EFE6DC] flex-shrink-0">
                                                <div class="truncate">
                                                    <p class="text-[11px] font-bold text-emerald-800 flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-xs">check_circle</span> Foto Siap
                                                    </p>
                                                    <p class="text-[9px] text-[#A39181] truncate">{{ $bukti_pembayaran->getClientOriginalName() }}</p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeBukti" class="text-rose-600 hover:text-rose-800 p-1 rounded-lg hover:bg-rose-50 cursor-pointer" title="Hapus foto">
                                                <span class="material-symbols-outlined text-base block">delete</span>
                                            </button>
                                        </div>
                                    @endif

                                    @error('bukti_pembayaran') 
                                        <span class="text-rose-600 text-[10px] font-semibold block mt-1.5">{{ $message }}</span> 
                                    @enderror
                                </div>
                            @else
                                <div class="bg-[#F5EDE4] p-3 rounded-xl border border-[#EFE6DC] text-xs text-[#786455] flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[#8C4A27]">info</span>
                                    <span>Silakan lakukan pembayaran tunai di kasir saat mengambil/menerima pesanan.</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if(!empty($cart))
                    <div class="border-t border-[#EFE6DC] pt-4 mt-6 bg-white sticky bottom-0">
                        <div class="flex justify-between items-center mb-3 text-xs">
                            <span class="text-[#786455] font-bold">Total Pembayaran:</span>
                            <span class="text-[#8C4A27] font-mono font-extrabold text-base">Rp {{ number_format($this->totalHarga, 0, ',', '.') }}</span>
                        </div>

                        <button wire:click="submitOrder" 
                                class="w-full py-3 rounded-xl bg-[#2A1810] hover:bg-[#3D2314] text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">send</span>
                            <span>Kirim Pesanan Sekarang</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- MODAL DETAIL PRODUK -->
    @if($showProductModal && $selectedProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
             wire:click.self="closeProductModal">
            <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden border border-[#EFE6DC] shadow-2xl relative">
                <button type="button" wire:click="closeProductModal" 
                        class="absolute top-3 right-3 z-10 bg-white/80 hover:bg-white text-[#2A1810] p-1.5 rounded-full backdrop-blur-xs transition-colors cursor-pointer shadow-md">
                    <span class="material-symbols-outlined block">close</span>
                </button>

                <div class="h-56 bg-[#F5EDE4] relative flex items-center justify-center">
                    @if($selectedProduct->foto && file_exists(public_path('storage/' . $selectedProduct->foto)))
                        <img src="{{ asset('storage/' . $selectedProduct->foto) }}" class="w-full h-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-6xl text-[#A39181]">bakery_dining</span>
                    @endif
                    
                    <span class="absolute top-3 left-3 text-xs font-bold px-2.5 py-1 rounded-full {{ $selectedProduct->stok > 0 ? 'bg-emerald-800 text-white' : 'bg-rose-800 text-white' }}">
                        {{ $selectedProduct->stok > 0 ? 'Stok Tersedia: '.$selectedProduct->stok : 'Stok Habis' }}
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <h3 class="font-extrabold text-lg text-[#2A1810]">{{ $selectedProduct->nama_produk }}</h3>
                        <p class="font-mono font-bold text-lg text-[#8C4A27] mt-1">
                            Rp {{ number_format($selectedProduct->harga, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="bg-[#FDFBF7] p-3.5 rounded-xl border border-[#EFE6DC]">
                        <h4 class="text-[11px] font-bold text-[#2A1810] uppercase tracking-wider mb-1">Deskripsi Menu:</h4>
                        <p class="text-xs text-[#786455] leading-relaxed">
                            {{ $selectedProduct->deskripsi ?: 'Roti Maryam lezat renyah di luar, lembut di dalam, dibuat dari bahan-bahan pilihan berkualitas tinggi.' }}
                        </p>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button type="button" wire:click="closeProductModal" class="flex-1 py-2.5 rounded-xl bg-[#F5EDE4] hover:bg-[#EFE6DC] text-[#786455] text-xs font-bold transition-colors cursor-pointer">
                            Tutup
                        </button>
                        
                        @if($selectedProduct->stok > 0)
                            <button type="button" wire:click="addToCart({{ $selectedProduct->id }})" class="flex-1 py-2.5 rounded-xl bg-[#8C4A27] hover:bg-[#703A1E] text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-md">
                                <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                                <span>+ Keranjang</span>
                            </button>
                        @else
                            <button disabled class="flex-1 py-2.5 rounded-xl bg-[#EFE6DC] text-[#A39181] text-xs font-bold cursor-not-allowed">
                                Stok Habis
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL PERBESAR QRIS -->
    @if($showQrisModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4" 
             wire:click.self="toggleQrisModal(false)">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center relative border border-[#EFE6DC] shadow-2xl">
                <button type="button" wire:click="toggleQrisModal(false)" 
                        class="absolute top-4 right-4 text-[#A39181] hover:text-[#2A1810] p-1.5 rounded-full hover:bg-[#F5EDE4] transition-colors cursor-pointer">
                    <span class="material-symbols-outlined block">close</span>
                </button>

                <div class="flex items-center justify-center gap-1 text-[#8C4A27] font-bold text-sm mb-3">
                    <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                    <span>Scan QRIS Pembayaran</span>
                </div>

                <div class="bg-[#F5EDE4] p-4 rounded-2xl border border-[#EFE6DC] inline-block mb-4 shadow-inner">
                    <img src="{{ $this->qrisFoto }}" alt="QRIS Maryam Go Large" 
                         class="w-64 h-64 object-contain mx-auto rounded-xl bg-white p-3">
                </div>

                <div class="space-y-2">
                    <a href="{{ $this->qrisFoto }}" download="qris-maryam-go.jpg" target="_blank" 
                       class="w-full py-2.5 rounded-xl bg-[#8C4A27] hover:bg-[#703A1E] text-white text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer shadow-md">
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>Download Gambar QRIS</span>
                    </a>
                    <button type="button" wire:click="toggleQrisModal(false)" 
                            class="w-full py-2 rounded-xl bg-transparent hover:bg-[#F5EDE4] text-[#786455] text-xs font-bold transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL NOTA SUKSES & DOWNLOAD NOTA GAMBAR -->
    @if($lastTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center border border-[#EFE6DC] shadow-2xl relative">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-2xl">check_circle</span>
                </div>
                <h3 class="font-extrabold text-base text-[#2A1810]">Pesanan Berhasil Dikirim!</h3>
                <p class="text-xs text-[#786455] mt-1">Simpan nota pesanan Anda di bawah ini.</p>

                <!-- AREA NOTA KASIR DENGAN BACKGROUND TERPISAH SUPAYA HASIL DOWNLOAD RAPI -->
                <div id="nota-cetak" class="my-4 p-4 rounded-2xl bg-[#F5EDE4] text-left text-xs space-y-3 font-mono border border-[#EFE6DC]">
                    <div class="text-center border-b border-dashed border-[#A39181] pb-2">
                        <h4 class="font-black text-sm text-[#2A1810] tracking-tight">MARYAM GO</h4>
                        <p class="text-[9px] text-[#8C7462]">Pemesanan Roti & Pastry</p>
                        <p class="text-[9px] text-[#A39181]">{{ $lastTransaction['tanggal'] }}</p>
                    </div>

                    <div class="space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-[#8C7462]">Kode TRX:</span>
                            <span class="font-bold text-[#2A1810]">{{ $lastTransaction['id'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#8C7462]">Pemesan:</span>
                            <span class="font-bold text-[#2A1810]">{{ $lastTransaction['nama'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#8C7462]">Metode:</span>
                            <span class="font-bold text-[#2A1810]">{{ $lastTransaction['metode'] }}</span>
                        </div>
                    </div>

                    <!-- Detail Item Pesanan -->
                    <div class="border-t border-dashed border-[#A39181] pt-2 space-y-1 text-[10px]">
                        <p class="font-bold text-[#2A1810] text-[9px] uppercase tracking-wider mb-1">Rincian Menu:</p>
                        @foreach($lastTransaction['items'] as $item)
                            <div class="flex justify-between text-[#2A1810]">
                                <span class="line-clamp-1">{{ $item['nama'] }} x{{ $item['jumlah'] }}</span>
                                <span class="font-bold">Rp {{ number_format($item['harga'] * $item['jumlah'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between border-t border-dashed border-[#A39181] pt-2 font-bold text-xs">
                        <span class="text-[#2A1810]">TOTAL:</span>
                        <span class="text-[#8C4A27]">Rp {{ number_format($lastTransaction['total'], 0, ',', '.') }}</span>
                    </div>

                    <div class="text-center text-[8px] text-[#A39181] pt-1">
                        *** Terima kasih telah memesan! ***
                    </div>
                </div>

                <!-- Tombol Action Nota -->
                <div class="space-y-2">
                    <button type="button" onclick="downloadNotaGambar()" 
                            class="w-full py-2.5 rounded-xl bg-[#8C4A27] hover:bg-[#703A1E] text-white text-xs font-bold flex items-center justify-center gap-2 cursor-pointer transition-colors shadow-md">
                        <span class="material-symbols-outlined text-base">download</span>
                        <span>Download Nota (Gambar PNG)</span>
                    </button>

                    <button wire:click="$set('lastTransaction', null)" 
                            class="w-full py-2 rounded-xl bg-transparent hover:bg-[#F5EDE4] text-[#786455] text-xs font-bold cursor-pointer transition-colors">
                        Selesai & Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        // JS Toast Notification
        window.addEventListener('show-toast', function(event) {
            var toast = document.createElement('div');
            var bgType = event.detail.type === 'error' ? 'bg-rose-800' : 'bg-[#2A1810]';
            toast.className = 'fixed bottom-6 right-6 text-white px-5 py-3 rounded-xl shadow-xl transition-all duration-300 z-50 text-xs font-bold flex items-center gap-2 ' + bgType;
            toast.innerHTML = '<span>' + event.detail.message + '</span>';
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 2500);
        });

        // JS Function Download Nota Langsung Jadi Gambar PNG (Cocok untuk HP & Laptop)
        function downloadNotaGambar() {
            var element = document.getElementById('nota-cetak');
            if (!element) return;

            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Menyiapkan gambar nota...', type: 'success' } 
            }));

            html2canvas(element, {
                scale: 3, // Skala tinggi agar tulisan di nota jernih & tajam saat disimpan
                backgroundColor: '#F5EDE4',
                useCORS: true
            }).then(function(canvas) {
                var link = document.createElement('a');
                link.download = 'Nota-MaryamGo-' + Date.now() + '.png';
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Nota berhasil tersimpan!', type: 'success' } 
                }));
            }).catch(function(err) {
                alert('Gagal mengunduh nota gambar: ' + err);
            });
        }
    </script>
</div>