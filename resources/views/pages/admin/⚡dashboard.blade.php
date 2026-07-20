<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Produk;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $halamanAktif = 'dashboard';

    // Property Pengaturan QRIS
    public $namaMerchant = 'MARYAM GO STORE';
    public $nmid = 'ID102026987654321';
    public $qrisStatus = 'aktif';
    public $qrisFoto;
    public $qrisFotoLama = null;

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->to('/login');
        }

        if (Auth::user()->status === 'nonaktif') {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            session()->flash('error_auth', 'Akun admin ini telah dinonaktifkan.');

            return redirect()->to('/login');
        }
    }

    public function gantiHalaman($halaman)
    {
        $this->halamanAktif = $halaman;
    }

    public function totalMacamProduk()
    {
        return Produk::count();
    }

    public function totalAdminTerdaftar()
    {
        return User::count();
    }

    public function produkTerbaru()
    {
        return Produk::latest()->take(5)->get();
    }

    public function simpanQris()
    {
        $this->validate([
            'namaMerchant' => 'required|string|max:100',
            'nmid' => 'required|string|max:50',
            'qrisStatus' => 'required|in:aktif,nonaktif',
            'qrisFoto' => 'nullable|image|max:2048', // Max 2MB
        ]);

        if ($this->qrisFoto) {
            // Logika simpan gambar QRIS ke storage (contoh simpan ke folder public/qris)
            // $path = $this->qrisFoto->store('qris', 'public');
            // $this->qrisFotoLama = $path;
        }

        session()->flash('qris_success', 'Konfigurasi & QRIS Merchant berhasil diperbarui!');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/login');
    }
};

?>
<div class="flex h-screen w-full overflow-hidden [font-family:'Plus_Jakarta_Sans',sans-serif]"
     style="min-height: 100vh; background: #F8FAFC;"
     x-data="{
         sidebarOpen: true,
         tabs: [
             { id: 'dashboard', title: 'Dashboard', icon: 'dashboard' }
         ],
         activeTab: @entangle('halamanAktif'),

         bukaTab(id, title, icon) {
             this.activeTab = id;
             let exist = this.tabs.find(t => t.id === id);
             if (!exist && title && icon) {
                 this.tabs.push({ id, title, icon });
             }
         },

         tutupTab(id) {
             if (this.tabs.length <= 1) return;

             let index = this.tabs.findIndex(t => t.id === id);
             this.tabs = this.tabs.filter(t => t.id !== id);

             if (this.activeTab === id) {
                 let nextTab = this.tabs[index] || this.tabs[index - 1];
                 this.activeTab = nextTab.id;
             }
         }
     }">

    <!-- Fonts & Icons Setup -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet" />
    
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .font-mono-data { font-family: 'JetBrains Mono', monospace; }

        .tag-ticket {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 11px;
            letter-spacing: 0.02em;
        }
        .tag-ticket .hole {
            width: 5px;
            height: 5px;
            border-radius: 99px;
            background: currentColor;
            opacity: 0.4;
            flex-shrink: 0;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 99px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>

    <!-- SIDEBAR PANEL (Sleek Dark Slate) -->
    <aside class="flex flex-col justify-between z-50 transition-all duration-300 w-66 flex-shrink-0 border-r border-slate-950/20"
           style="background: linear-gradient(180deg, #0F172A 0%, #020617 100%);"
           x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full">

        <div>
            <!-- Brand/Logo Header -->
            <div class="px-6 py-5 flex items-center gap-3.5 border-b border-white/[0.04]">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-xl transition-transform duration-300 hover:scale-105"
                     style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); box-shadow: 0 4px 20px rgba(99,102,241,0.35);">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">storefront</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-white tracking-wide font-display">Maryam Go</h1>
                    <p class="text-[10px] font-semibold tracking-wide text-slate-400 mt-0.5">Console Platform</p>
                </div>
            </div>

            <!-- User Profile Card -->
            <div class="p-4 border-b border-white/[0.04]">
                <div class="rounded-xl p-3.5 bg-white/[0.02] border border-white/[0.05] shadow-inner">
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Petugas Aktif</p>
                    <p class="font-semibold text-white text-xs truncate mt-1">{{ Auth::user()->nama ?? 'Admin Utama' }}</p>
                    <div class="flex items-center gap-2 mt-2.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-[10px] font-bold tracking-wide text-emerald-400">Online System</span>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-4 space-y-5">
                <!-- Sistem Utama -->
                <div>
                    <p class="px-2.5 mb-2.5 text-[9px] font-bold uppercase tracking-widest text-slate-500 select-none">Sistem Utama</p>
                    <button @click.prevent="bukaTab('dashboard', 'Dashboard', 'dashboard')"
                            class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-left text-xs font-semibold transition-all duration-200 cursor-pointer"
                            :class="activeTab === 'dashboard' ? 'bg-gradient-to-r from-[#6366F1] to-[#4F46E5] text-white shadow-lg shadow-indigo-950/50' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'">
                        <span class="material-symbols-outlined text-lg" :style="activeTab === 'dashboard' && 'font-variation-settings: \'FILL\' 1;'">dashboard</span>
                        <span>Dashboard Overview</span>
                    </button>
                </div>

                <!-- Master Data -->
                <div class="space-y-1">
                    <p class="px-2.5 mb-2.5 text-[9px] font-bold uppercase tracking-widest text-slate-500 select-none">Data Operasional</p>
                    
                    <!-- Kelola Produk -->
                    <button @click.prevent="bukaTab('produk', 'Kelola Produk', 'inventory_2')"
                            class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-left text-xs font-semibold transition-all duration-200 cursor-pointer"
                            :class="activeTab === 'produk' ? 'bg-gradient-to-r from-[#6366F1] to-[#4F46E5] text-white shadow-lg shadow-indigo-950/50' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'">
                        <span class="material-symbols-outlined text-lg" :style="activeTab === 'produk' && 'font-variation-settings: \'FILL\' 1;'">inventory_2</span>
                        <span>Kelola Master Produk</span>
                    </button>

                    <!-- Kelola Transaksi -->
                    <button @click.prevent="bukaTab('transaksi', 'Kelola Transaksi', 'receipt_long')"
                            class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-left text-xs font-semibold transition-all duration-200 cursor-pointer"
                            :class="activeTab === 'transaksi' ? 'bg-gradient-to-r from-[#6366F1] to-[#4F46E5] text-white shadow-lg shadow-indigo-950/50' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'">
                        <span class="material-symbols-outlined text-lg" :style="activeTab === 'transaksi' && 'font-variation-settings: \'FILL\' 1;'">receipt_long</span>
                        <span>Kelola Transaksi</span>
                    </button> 
                </div>

                <!-- Konfigurasi Sistem -->
                <div class="space-y-1">
                    <p class="px-2.5 mb-2.5 text-[9px] font-bold uppercase tracking-widest text-slate-500 select-none">Pengaturan Kasir</p>
                    
                    <!-- Seting QRIS -->
                    <button @click.prevent="bukaTab('qris', 'Pengaturan QRIS', 'qr_code_2')"
                            class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-left text-xs font-semibold transition-all duration-200 cursor-pointer"
                            :class="activeTab === 'qris' ? 'bg-gradient-to-r from-[#6366F1] to-[#4F46E5] text-white shadow-lg shadow-indigo-950/50' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white'">
                        <span class="material-symbols-outlined text-lg" :style="activeTab === 'qris' && 'font-variation-settings: \'FILL\' 1;'">qr_code_2</span>
                        <span>Pengaturan QRIS</span>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer Action -->
        <div class="p-4 border-t border-white/[0.04]">
            <button wire:click="logout"
                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-xs transition-all duration-200 cursor-pointer bg-white/[0.02] text-slate-400 border border-white/[0.05] hover:bg-rose-600 hover:text-white hover:border-rose-600 hover:shadow-lg hover:shadow-rose-950/20">
                <span class="material-symbols-outlined text-base">logout</span>
                <span>Keluar Sistem</span>
            </button>
        </div>
    </aside>

    <!-- MAIN APP WRAPPER (Clean Slate Light) -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#F8FAFC]">

        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white flex items-center px-6 justify-between z-40 flex-shrink-0 border-b border-slate-200 shadow-sm shadow-slate-100/40">
            <div class="flex items-center gap-4">
                <button @click.prevent="sidebarOpen = !sidebarOpen"
                        type="button"
                        class="p-2 rounded-xl cursor-pointer inline-flex items-center justify-center transition-all border border-slate-200 text-slate-600 hover:bg-slate-50 active:scale-95">
                    <span class="material-symbols-outlined text-xl" x-text="sidebarOpen ? 'menu_open' : 'menu'"></span>
                </button>

                <!-- Breadcrumb Dinamis -->
                <div class="text-xs font-semibold flex items-center gap-2 select-none text-slate-400">
                    <span class="uppercase text-[9px] tracking-widest font-bold">Workspace</span>
                    <span class="material-symbols-outlined text-sm text-slate-300">chevron_right</span>
                    <span class="font-bold font-display text-slate-900 text-sm" 
                          x-text="activeTab === 'dashboard' ? 'Dashboard' : (activeTab === 'produk' ? 'Kelola Produk' : (activeTab === 'transaksi' ? 'Kelola Transaksi' : 'Pengaturan QRIS'))"></span>
                </div>
            </div>

            <div class="flex items-center gap-3.5">
                <span class="material-symbols-outlined cursor-pointer p-2 rounded-xl text-lg text-slate-400 border border-transparent transition-all hover:bg-slate-50 hover:text-slate-900">search</span>
                <span class="material-symbols-outlined cursor-pointer p-2 rounded-xl text-lg text-slate-400 border border-transparent transition-all hover:bg-slate-50 hover:text-slate-900">notifications</span>
                <div class="h-5 w-px bg-slate-200"></div>
                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-600 border border-slate-200 shadow-inner cursor-pointer hover:border-indigo-500 transition-colors">
                    <span class="material-symbols-outlined text-lg">account_circle</span>
                </div>
            </div>
        </header>

        <!-- INTERACTIVE SUB-TABS BAR -->
        <div class="h-12 flex items-end px-6 gap-1.5 flex-shrink-0 select-none bg-slate-100 border-b border-slate-200">
            <template x-for="tab in tabs" :key="tab.id">
                <div @click="activeTab = tab.id"
                     class="flex items-center gap-2.5 px-4 py-2 rounded-t-xl cursor-pointer transition-all duration-200 text-xs font-bold relative group"
                     :class="activeTab === tab.id
                        ? 'bg-white text-indigo-600 shadow-[0_-3px_0_0_#4F46E5_inset] shadow-sm'
                        : 'text-slate-500 hover:bg-white/60 hover:text-slate-900'">

                    <span class="material-symbols-outlined text-base" x-text="tab.icon" :style="activeTab === tab.id && 'font-variation-settings: \'FILL\' 1;'"></span>
                    <span class="whitespace-nowrap font-display tracking-wide" x-text="tab.title"></span>

                    <template x-if="tabs.length > 1">
                        <span @click.stop="tutupTab(tab.id)"
                              class="material-symbols-outlined text-[14px] rounded-md p-0.5 ml-1 transition-all text-slate-300 hover:text-white hover:bg-rose-500">
                            close
                        </span>
                    </template>
                </div>
            </template>
        </div>

        <!-- MAIN VIEWPORT CONTENT -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- TAB PANEL: DASHBOARD VIEW -->
                <div x-show="activeTab === 'dashboard'"
                     class="space-y-6"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight font-display text-slate-900">Overview Dashboard</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Analisis metrik data produk terdaftar dan aktivitas logistik kasir.</p>
                        </div>
                        <div class="text-xs bg-white px-3.5 py-2 rounded-xl font-semibold flex items-center gap-2 self-start sm:self-auto border border-slate-200 shadow-sm text-slate-600">
                            <span class="material-symbols-outlined text-base text-slate-400">calendar_today</span>
                            <span>{{ date('d F Y') }}</span>
                        </div>
                    </div>

                    <!-- Widgets Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                        <!-- Widget 1 -->
                        <div class="bg-white p-6 rounded-2xl flex justify-between items-center relative overflow-hidden border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 group">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-indigo-400 to-indigo-600"></div>
                            <div class="pl-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Jenis Produk Terdaftar</p>
                                <h3 class="text-xl font-bold mt-1.5 font-display text-slate-900">
                                    <span class="text-3xl font-black text-indigo-600 tracking-tight">{{ $this->totalMacamProduk() }}</span> Macam
                                </h3>
                                <div class="mt-3 flex items-center gap-1.5 text-[9px] font-bold uppercase text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md w-max">
                                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">verified</span>
                                    <span>Master Data Valid</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 bg-indigo-50 text-indigo-600 group-hover:scale-110 transition-transform duration-300">
                                <span class="material-symbols-outlined text-2xl">package_2</span>
                            </div>
                        </div>

                        <!-- Widget 2 -->
                        <div class="bg-white p-6 rounded-2xl flex justify-between items-center relative overflow-hidden border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 group">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-purple-400 to-purple-600"></div>
                            <div class="pl-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Petugas Kasir</p>
                                <h3 class="text-xl font-bold mt-1.5 font-display text-slate-900">
                                    <span class="text-3xl font-black text-purple-600 tracking-tight">{{ $this->totalAdminTerdaftar() }}</span> Operator
                                </h3>
                                <div class="mt-3 flex items-center gap-1.5 text-[9px] font-bold uppercase text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md w-max">
                                    <span class="material-symbols-outlined text-xs">group</span>
                                    <span>Hak Akses Terjaga</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 bg-purple-50 text-purple-600 group-hover:scale-110 transition-transform duration-300">
                                <span class="material-symbols-outlined text-2xl">groups</span>
                            </div>
                        </div>
                    </div>

                    <!-- DATA LIST: PRODUK TERBARU -->
                    <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
                        <!-- Header Box Utama -->
                        <div class="px-6 py-4.5 flex justify-between items-center select-none bg-slate-50/50 border-b border-slate-200">
                            <div>
                                <h3 class="font-bold text-xs uppercase tracking-wider font-display text-slate-900">Produk Baru Ditambahkan</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Daftar entri produk teranyar yang masuk ke dalam repositori.</p>
                            </div>
                            <span class="text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200/40">
                                Live Database
                            </span>
                        </div>

                        <!-- List Content Container -->
                        <div class="w-full overflow-x-auto">
                            <div class="min-w-[700px]">
                                <!-- Header Kolom -->
                                <div class="flex items-center px-6 py-3.5 text-[10px] font-bold uppercase tracking-widest bg-slate-50 text-slate-400 border-b border-slate-200 select-none">
                                    <div style="width:40%;">Nama Produk</div>
                                    <div class="text-center" style="width:25%;">Kode / SKU</div>
                                    <div class="text-right" style="width:18%;">Harga Satuan</div>
                                    <div class="text-center" style="width:17%;">Stok Sisa</div>
                                </div>

                                <!-- Baris Data Dinamis -->
                                <div class="divide-y divide-slate-100">
                                    @forelse($this->produkTerbaru() as $produk)
                                        <div class="flex items-center px-6 py-4 transition-colors hover:bg-slate-50/60" wire:key="dashboard-prod-{{ $produk->id }}">
                                            <div class="font-semibold text-xs text-slate-800 truncate pr-4" style="width:40%;">
                                                {{ $produk->nama_produk }}
                                            </div>
                                            <div class="flex justify-center" style="width:25%;">
                                                <span class="tag-ticket font-mono-data font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                    <span class="hole"></span>
                                                    {{ $produk->kode_produk ?? '—' }}
                                                </span>
                                            </div>
                                            <div class="text-right font-bold text-xs font-mono-data text-slate-900" style="width:18%;">
                                                Rp {{ number_format($produk->harga, 0, ',', '.') }}
                                            </div>
                                            <div class="flex justify-center" style="width:17%;">
                                                <span class="tag-ticket font-bold border"
                                                      style="{{ $produk->stok > 10 ? 'background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE;' : 'background:#FFF1F2; color:#B91C1C; border-color:#FECDD3;' }}">
                                                    <span class="hole"></span>
                                                    {{ $produk->stok }} pcs
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-16 text-center text-slate-400" wire:key="dashboard-prod-empty">
                                            <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">inventory_2</span>
                                            <p class="text-xs font-semibold tracking-wide">Belum ada data produk terdaftar</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">Data inventaris toko yang Anda tambahkan akan muncul di sini.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB PANEL: MASTER DATA PRODUK VIEW -->
                <div x-show="activeTab === 'produk'"
                     class="space-y-4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    <div>
                        <h2 class="text-xl font-bold tracking-tight font-display text-slate-900">Manajemen Produk Toko</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola klaster data inventaris sistem aplikasi Maryam Go secara terpusat.</p>
                    </div>

                    <!-- Livewire Container Produk -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <livewire:pages::admin.data :key="'sub-produk-aktif'" />
                    </div>
                </div>

                <!-- TAB PANEL: KELOLA TRANSAKSI VIEW -->
                <div x-show="activeTab === 'transaksi'"
                     class="space-y-4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    <div>
                        <h2 class="text-xl font-bold tracking-tight font-display text-slate-900">Verifikasi Transaksi Pelanggan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Validasi berkas struk pembayaran dan antrean belanja kasir Maryam Go.</p>
                    </div>

                    <!-- Livewire Container Transaksi -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <livewire:pages::admin.transaksi :key="'sub-transaksi-aktif'" />
                    </div>
                </div>

                <!-- TAB PANEL: PENGATURAN QRIS VIEW -->
                <div x-show="activeTab === 'qris'"
                     class="space-y-6"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    <div>
                        <h2 class="text-xl font-bold tracking-tight font-display text-slate-900">Pengaturan QRIS Merchant</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Konfigurasi nama merchant, kode NMID, dan barcode QRIS pembayaran toko.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <livewire:pages::admin.pengaturan :key="'sub-transaksi-aktif'" />
                    </div>

                </div>

            </div>
        </div>
    </main>
</div>