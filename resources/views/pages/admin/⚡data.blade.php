<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use App\Models\Produk;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    // State untuk Pencarian dan Filter
    public $search = '';
    
    // State untuk Form CRUD (Sudah ditambahkan kode, stok, dan foto)
    public $id_produk, $nama_produk, $kode_produk, $stok, $deskripsi, $harga, $foto, $foto_lama;
    
    // State UI Modal
    public $isModalTerbuka = false;
    public $modeEdit = false;

    protected $queryString = ['search' => ['except' => '']];

    /**
     * Data produk diakses via Computed Property.
     * Di dalam Blade dipanggil menggunakan: $this->products
     */
    #[Computed]
    public function products()
    {
        return Produk::where('nama_produk', 'like', '%' . $this->search . '%')
            ->orWhere('kode_produk', 'like', '%' . $this->search . '%')
            ->latest()
            ->get();
    }

    // Reset isi form input
    public function resetInputFields()
    {
        $this->id_produk = null;
        $this->nama_produk = '';
        $this->kode_produk = '';
        $this->stok = 0;
        $this->deskripsi = '';
        $this->harga = '';
        $this->foto = null;
        $this->foto_lama = null;
        $this->resetValidation();
    }

    // Kontrol Modal
    public function bukaModal()
    {
        $this->isModalTerbuka = true;
    }

    public function tutupModal()
    {
        $this->isModalTerbuka = false;
        $this->resetInputFields();
        $this->modeEdit = false;
    }

    public function simpanProduk()
    {
        // 1. Atur Rules Validasi
        $rules = [
            'nama_produk' => 'required|string|max:255',
            'harga'       => 'required|numeric|min:0',
            'stok'        => 'required|integer|min:0',
            'kode_produk' => 'nullable|string|max:50|unique:produks,kode_produk,' . $this->id_produk,
            'deskripsi'   => 'nullable|string',
            'foto'        => $this->id_produk ? 'nullable|image|max:2048' : 'required|image|max:2048', 
        ];

        $this->validate($rules);

        // 2. Generate Slug Otomatis
        $slug = Str::slug($this->nama_produk);

        // 3. Handle Proses Upload Foto
        $namaFoto = $this->foto_lama;
        
        if ($this->foto instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            // Hapus foto lama dari storage jika sedang edit produk
            if ($this->foto_lama && Storage::disk('public')->exists($this->foto_lama)) {
                Storage::disk('public')->delete($this->foto_lama);
            }
            // Simpan foto baru
            $namaFoto = $this->foto->store('produk', 'public');
        }

        // 4. Eksekusi Simpan (Create / Update)
        Produk::updateOrCreate(['id' => $this->id_produk], [
            'nama_produk' => $this->nama_produk,
            'kode_produk' => $this->kode_produk,
            'slug'        => $slug,
            'deskripsi'   => $this->deskripsi,
            'harga'       => $this->harga,
            'stok'        => $this->stok,
            'status'      => $this->stok > 0 ? 'tersedia' : 'habis',
            'foto'        => $namaFoto,
        ]);

        session()->flash('message', $this->id_produk ? 'Produk berhasil diperbarui!' : 'Produk berhasil ditambahkan!');

        $this->tutupModal();
        $this->reset('foto'); 
    }

    // Trigger Edit Data
    public function edit($id)
    {
        $produk = Produk::findOrFail($id);
        $this->id_produk   = $produk->id;
        $this->nama_produk = $produk->nama_produk;
        $this->kode_produk = $produk->kode_produk;
        $this->stok        = $produk->stok;
        $this->deskripsi   = $produk->deskripsi;
        $this->harga       = $produk->harga;
        $this->foto_lama   = $produk->foto;

        $this->modeEdit = true;
        $this->bukaModal();
    }

    // Hapus Data
    public function hapus($id)
    {
        $produk = Produk::findOrFail($id);
        
        // Bersihkan foto dari storage sebelum data dihapus
        if ($produk->foto && Storage::disk('public')->exists($produk->foto)) {
            Storage::disk('public')->delete($produk->foto);
        }

        $produk->delete();
        session()->flash('message', 'Produk berhasil dihapus dari sistem.');
    }
};

?>
<div class="p-6 text-slate-700 antialiased w-full">
    <!-- Flash Message Notification -->
    @if (session()->has('message'))
        <div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium shadow-xs">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- BAGIAN TOOLBAR (FIXED FLEX INPUT GROUP) -->
    <div class="block w-full mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
            
            <!-- Sisi Kiri: Input Search dengan Flex Group -->
            <div class="w-full md:w-80 flex items-center bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-2xs focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 transition-all">
                <span class="material-symbols-outlined text-slate-400 text-[20px] mr-2 select-none flex-shrink-0">search</span>
                <input wire:model.live="search" type="text" placeholder="Cari nama atau kode produk..." 
                       class="w-full bg-transparent p-0 text-sm focus:outline-none focus:ring-0 border-none text-slate-700 placeholder-slate-400 block h-5"/>
            </div>
            
            <!-- Sisi Kanan: Tombol Tambah -->
            <div class="w-full md:w-auto flex justify-start md:justify-end">
                <button wire:click="bukaModal" type="button" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg font-semibold text-sm transition-all shadow-xs cursor-pointer w-full md:w-max whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tambah Baris Data
                </button>
            </div>
        </div>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs w-full block">
        <div class="overflow-x-auto w-full block">
            <!-- REVISI: Hapus whitespace-nowrap global agar kolom bisa fleksibel patah baris -->
            <table class="w-full text-left border-collapse table-auto">
                <thead class="bg-slate-50 border-b border-slate-200 select-none whitespace-nowrap">
                    <tr>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider w-[60px] text-center">#</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider w-[80px]">Foto</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama & Kode Item</th>
                        <!-- REVISI: Kunci lebar kolom header deskripsi -->
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider w-[240px] max-w-[240px]">Deskripsi</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Harga Satuan</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Stok</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-[120px]">Status</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-[120px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($this->products as $index => $produk)
                        <tr class="hover:bg-slate-50/60 transition-colors duration-150" wire:key="row-{{ $produk->id }}">
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-400 align-middle whitespace-nowrap">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 align-middle whitespace-nowrap">
                                @if($produk->foto)
                                    <div style="width: 48px; height: 48px; min-width: 48px; min-height: 48px; max-width: 48px; max-height: 48px;" class="rounded-lg overflow-hidden border border-slate-200 shadow-2xs bg-white">
                                        <img src="{{ asset('storage/' . $produk->foto) }}" alt="Foto {{ $produk->nama_produk }}" style="width: 100%; height: 100%; object-fit: cover;" class="block">
                                    </div>
                                @else
                                    <div style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;" class="rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200 text-slate-400" title="Tidak ada foto">
                                        <span class="material-symbols-outlined text-[18px]">image_not_supported</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <span class="font-semibold text-slate-800 block mb-0.5 whitespace-normal">{{ $produk->nama_produk }}</span>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[10px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded" title="Slug URL">{{ $produk->slug }}</span>
                                    @if($produk->kode_produk)
                                        <span class="text-[10px] text-emerald-700 font-mono bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded" title="Kode Produk">{{ $produk->kode_produk }}</span>
                                    @endif
                                </div>
                            </td>
                            <!-- REVISI TOTAL: Kolom Deskripsi dikunci paksa agar teks turun ke bawah & tidak melar ke kanan -->
                            <td class="px-6 py-4 text-slate-500 text-sm w-[240px] max-w-[240px] whitespace-normal break-words align-middle">
                                {{ $produk->deskripsi ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900 align-middle whitespace-nowrap">
                                Rp {{ number_format($produk->harga, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-sm text-slate-700 align-middle whitespace-nowrap">
                                {{ $produk->stok }}
                            </td>
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide {{ $produk->status === 'tersedia' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ ucfirst($produk->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center align-middle whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $produk->id }})" class="p-1.5 bg-slate-50 hover:bg-amber-50 border border-slate-200 hover:border-amber-200 text-slate-500 hover:text-amber-700 rounded-md transition-all cursor-pointer shadow-2xs" title="Edit Baris">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button wire:click="hapus({{ $produk->id }})" wire:confirm="Yakin ingin menghapus produk ini?" class="p-1.5 bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-500 hover:text-rose-600 rounded-md transition-all cursor-pointer shadow-2xs" title="Hapus Baris">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-16 text-center text-slate-400">
                                <span class="material-symbols-outlined text-[48px] text-slate-300 block mb-3 select-none">query_stats</span>
                                <p class="text-sm font-medium">Tidak ditemukan kecocokan data produk pada sistem.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FORM ENGINE -->
    @if($isModalTerbuka)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300" wire:click="tutupModal"></div>

            <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-auto z-10 overflow-hidden my-auto flex flex-col">
                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">
                        {{ $modeEdit ? 'Edit Data Produk' : 'Tambah Produk Baru' }}
                    </h3>
                    <button wire:click="tutupModal" type="button" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-md hover:bg-slate-200/50 flex items-center justify-center cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form wire:submit.prevent="simpanProduk" class="m-0 p-0">
                    <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                        
                        <!-- Upload Gambar Produk (TOTAL FIX OVERFLOW & STRETCH) -->
                        <div class="block w-full">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Foto Produk</label>
                            
                            <div class="block w-full bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <div class="flex items-center gap-4">
                                    <!-- Kotak Preview Foto -->
                                    <div style="width: 70px; height: 70px; min-width: 70px; min-height: 70px; max-width: 70px; max-height: 70px;" class="rounded-lg overflow-hidden border border-slate-350 bg-white flex items-center justify-center shadow-2xs flex-shrink-0">
                                        @if ($foto)
                                            <img src="{{ $foto->temporaryUrl() }}" style="width: 100%; height: 100%; object-fit: cover;" class="block">
                                        @elseif ($foto_lama)
                                            <img src="{{ asset('storage/' . $foto_lama) }}" style="width: 100%; height: 100%; object-fit: cover;" class="block">
                                        @else
                                            <span class="material-symbols-outlined text-[24px] text-slate-400 select-none">add_a_photo</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Area Tombol Aksi Upload -->
                                    <div class="block">
                                        <input type="file" wire:model="foto" id="upload-foto" class="hidden" accept="image/*" />
                                        <label for="upload-foto" class="inline-flex items-center justify-center bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg font-semibold text-xs shadow-2xs cursor-pointer transition-all active:scale-95">
                                            Pilih Gambar
                                        </label>
                                        <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (Maks. 2MB)</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Indikator Menunggu Upload Selesai -->
                            <div wire:loading wire:target="foto" class="text-[11px] text-amber-600 font-medium mt-1.5 animate-pulse block">
                                Mengunggah berkas gambar preview...
                            </div>
                            @error('foto') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Nama Produk -->
                        <div class="block">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Produk</label>
                            <input type="text" wire:model="nama_produk" class="w-full bg-white border @error('nama_produk') border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 transition-all block"/>
                            @error('nama_produk') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Baris Grid untuk Kode Produk & Stok -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Kode Produk -->
                            <div class="block">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Kode SKU</label>
                                <input type="text" wire:model="kode_produk" placeholder="Contoh: BRG-001" class="w-full bg-white border @error('kode_produk') border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 transition-all block"/>
                                @error('kode_produk') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Jumlah Stok -->
                            <div class="block">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Jumlah Stok</label>
                                <input type="number" wire:model="stok" min="0" class="w-full bg-white border @error('stok') border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 transition-all block"/>
                                @error('stok') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Harga Satuan -->
                        <div class="block">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Harga Satuan</label>
                            <div class="w-full flex items-center bg-white border @error('harga') border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 @enderror rounded-lg px-3 py-2 shadow-2xs focus-within:ring-2 transition-all">
                                <span class="text-slate-400 text-sm font-medium mr-2 select-none flex-shrink-0">Rp</span>
                                <input type="number" wire:model="harga" class="w-full bg-transparent p-0 text-sm focus:outline-none focus:ring-0 border-none text-slate-700 block h-5"/>
                            </div>
                            @error('harga') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Deskripsi -->
                        <div class="block">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Deskripsi Produk</label>
                            <textarea wire:model="deskripsi" rows="3" class="w-full bg-white border @error('deskripsi') border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 @enderror rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 transition-all resize-none block"></textarea>
                            @error('deskripsi') <span class="text-xs text-rose-600 font-medium block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Modal Footer Action -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" wire:click="tutupModal" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-lg hover:bg-slate-200 transition-colors cursor-pointer">
                            Batalkan
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-5 py-2 rounded-lg font-semibold text-sm transition-all shadow-xs active:scale-98 cursor-pointer">
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>