<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Produk;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $halamanAktif = 'dashboard';

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
     style="min-height: 100vh; background:#F3F4EE;"
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

    <!-- Fonts: Space Grotesk (display), Plus Jakarta Sans (body), JetBrains Mono (data/codes) -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet" />
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .font-mono-data { font-family: 'JetBrains Mono', monospace; }

        /* Signature element: market-ticket tag with a punched hole, used for SKU / stock chips */
        .tag-ticket {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px 3px 8px;
            border-radius: 999px;
        }
        .tag-ticket .hole {
            width: 5px;
            height: 5px;
            border-radius: 999px;
            background: currentColor;
            opacity: .55;
            flex-shrink: 0;
        }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #D8D6C7; border-radius: 999px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>

    <!-- SIDEBAR PANEL -->
    <aside class="flex flex-col justify-between z-50 transition-all duration-300 w-64 flex-shrink-0"
           style="background: linear-gradient(180deg, #14261E 0%, #0E1D17 100%); border-right: 1px solid #0A140F;"
           x-show="sidebarOpen">

        <div>
            <!-- Brand/Logo Header -->
            <div class="px-6 py-5 flex items-center gap-3" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white shadow-lg"
                     style="background: linear-gradient(135deg, #3FA46B 0%, #2A7A4C 100%); box-shadow: 0 4px 14px rgba(63,164,107,0.35);">
                    <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">storefront</span>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-white tracking-wide leading-none font-display">Maryam Go</h1>
                    <p class="text-[10px] font-semibold mt-1 tracking-wide" style="color:#7FA893;">Admin Console</p>
                </div>
            </div>

            <!-- User Profile Card -->
            <div class="p-4" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                <div class="rounded-xl p-3" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
                    <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#5E7A6A;">Petugas Aktif</p>
                    <p class="font-semibold text-white text-xs truncate mt-0.5">{{ Auth::user()->nama ?? 'Admin Utama' }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60" style="background:#3FA46B;"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5" style="background:#3FA46B;"></span>
                        </span>
                        <span class="text-[10px] font-semibold" style="color:#3FA46B;">Sistem Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-4 space-y-6">
                <div>
                    <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest select-none" style="color:#4C6759;">Sistem Utama</p>
                    <button @click.prevent="bukaTab('dashboard', 'Dashboard', 'dashboard')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left text-xs font-semibold transition-all duration-150"
                            :style="activeTab === 'dashboard' ? 'background: linear-gradient(135deg, #3FA46B 0%, #2A7A4C 100%); color: white; box-shadow: 0 2px 10px rgba(63,164,107,0.3);' : 'color:#8CA598;'"
                            onmouseover="if(!this.classList.contains('active')) this.style.background='rgba(255,255,255,0.05)'"
                            onmouseout="if(this.getAttribute('style').indexOf('linear-gradient')===-1) this.style.background='transparent'">
                        <span class="material-symbols-outlined text-lg">dashboard</span>
                        <span>Dashboard</span>
                    </button>
                </div>

                <div>
                    <p class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest select-none" style="color:#4C6759;">Master Data</p>
                    <button @click.prevent="bukaTab('produk', 'Kelola Produk', 'inventory_2')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left text-xs font-semibold transition-all duration-150"
                            :style="activeTab === 'produk' ? 'background: linear-gradient(135deg, #3FA46B 0%, #2A7A4C 100%); color: white; box-shadow: 0 2px 10px rgba(63,164,107,0.3);' : 'color:#8CA598;'"
                            onmouseover="if(this.getAttribute('style').indexOf('linear-gradient')===-1) this.style.background='rgba(255,255,255,0.05)'"
                            onmouseout="if(this.getAttribute('style').indexOf('linear-gradient')===-1) this.style.background='transparent'">
                        <span class="material-symbols-outlined text-lg">inventory_2</span>
                        <span>Kelola Produk</span>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer Action -->
        <div class="p-4" style="border-top: 1px solid rgba(255,255,255,0.06);">
            <button wire:click="logout"
                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-xs transition-colors duration-150"
                    style="background: rgba(255,255,255,0.04); color:#B8C9BE; border:1px solid rgba(255,255,255,0.06);"
                    onmouseover="this.style.background='#C1502F'; this.style.color='white'; this.style.borderColor='#C1502F';"
                    onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.color='#B8C9BE'; this.style.borderColor='rgba(255,255,255,0.06)';">
                <span class="material-symbols-outlined text-base">logout</span>
                <span>Keluar Sistem</span>
            </button>
        </div>
    </aside>

    <!-- MAIN APP WRAPPER -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden" style="background:#F3F4EE;">

        <!-- HEADER TOP BAR -->
        <header class="h-14 bg-white flex items-center px-6 justify-between z-40 flex-shrink-0" style="border-bottom: 1px solid #E5E3D6;">
            <div class="flex items-center gap-3">
                <button @click.prevent="sidebarOpen = !sidebarOpen"
                        type="button"
                        class="p-1.5 rounded-lg cursor-pointer inline-flex items-center justify-center transition-colors"
                        style="color:#5E6B60; border:1px solid #E5E3D6;"
                        onmouseover="this.style.background='#F3F4EE'" onmouseout="this.style.background='transparent'">
                    <span class="material-symbols-outlined text-xl" x-text="sidebarOpen ? 'menu_open' : 'menu'"></span>
                </button>

                <div class="text-xs font-semibold flex items-center gap-1 select-none" style="color:#9CA396;">
                    <span class="uppercase text-[10px] tracking-wide">System</span>
                    <span class="material-symbols-outlined text-sm" style="color:#D3D1C2;">chevron_right</span>
                    <span class="font-bold font-display" style="color:#1B241D;" x-text="activeTab === 'dashboard' ? 'Dashboard' : 'Kelola Produk'"></span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined cursor-pointer p-1.5 rounded-lg text-lg transition-colors" style="color:#9CA396;" onmouseover="this.style.background='#F3F4EE'; this.style.color='#1B241D';" onmouseout="this.style.background='transparent'; this.style.color='#9CA396';">search</span>
                <span class="material-symbols-outlined cursor-pointer p-1.5 rounded-lg text-lg transition-colors" style="color:#9CA396;" onmouseover="this.style.background='#F3F4EE'; this.style.color='#1B241D';" onmouseout="this.style.background='transparent'; this.style.color='#9CA396';">notifications</span>
                <div class="h-5 w-px" style="background:#E5E3D6;"></div>
                <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background:#EBEADA; color:#5E6B60; border:1px solid #E5E3D6;">
                    <span class="material-symbols-outlined text-base">account_circle</span>
                </div>
            </div>
        </header>

        <!-- INTERACTIVE SUB-TABS BAR -->
        <div class="h-11 flex items-end px-4 gap-1 flex-shrink-0 select-none" style="background:#EAE9DC; border-bottom: 1px solid #DCDACB;">
            <template x-for="tab in tabs" :key="tab.id">
                <div @click="activeTab = tab.id"
                     class="flex items-center gap-2 px-4 py-2 rounded-t-lg cursor-pointer transition-all text-xs font-bold relative"
                     :style="activeTab === tab.id
                        ? 'background:#FFFFFF; color:#2A7A4C; box-shadow: 0 -2px 0 0 #3FA46B inset;'
                        : 'background:transparent; color:#7A8074;'"
                     onmouseover="if(this.style.background==='transparent' || this.style.background==='') this.style.background='rgba(255,255,255,0.4)'"
                     onmouseout="if(this.style.boxShadow==='') this.style.background='transparent'">

                    <span class="material-symbols-outlined text-sm" x-text="tab.icon"></span>
                    <span class="whitespace-nowrap font-display" x-text="tab.title"></span>

                    <template x-if="tabs.length > 1">
                        <span @click.stop="tutupTab(tab.id)"
                              class="material-symbols-outlined text-xs rounded p-0.5 ml-1 transition-all"
                              style="color:#B8B6A6;"
                              onmouseover="this.style.color='white'; this.style.background='#C1502F';"
                              onmouseout="this.style.color='#B8B6A6'; this.style.background='transparent';">
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
                     x-transition:enter="transition ease-out duration-150">

                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight font-display" style="color:#1B241D;">Overview Dashboard</h2>
                            <p class="text-xs mt-0.5" style="color:#7A8074;">Pantau perkembangan data master produk dan internal kasir.</p>
                        </div>
                        <div class="text-xs bg-white px-3 py-1.5 rounded-lg font-semibold flex items-center gap-1.5 select-none"
                             style="color:#5E6B60; border:1px solid #E5E3D6; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                            <span class="material-symbols-outlined text-sm" style="color:#9CA396;">calendar_today</span>
                            <span>{{ date('d F Y') }}</span>
                        </div>
                    </div>

                    <!-- Widgets Grid -->
                    <div class="flex flex-col sm:flex-row gap-4 w-full">
                        <div class="flex-1 bg-white p-5 rounded-xl flex justify-between items-center relative overflow-hidden"
                             style="border:1px solid #E5E3D6; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <div class="absolute left-0 top-0 bottom-0 w-1" style="background: linear-gradient(180deg, #3FA46B, #2A7A4C);"></div>
                            <div class="pl-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#9CA396;">Jenis Produk Terdaftar</p>
                                <h3 class="text-xl font-bold mt-1 font-display" style="color:#1B241D;"><span class="text-2xl font-extrabold" style="color:#2A7A4C;">{{ $this->totalMacamProduk() }}</span> Produk</h3>
                                <div class="mt-2 flex items-center gap-1 text-[10px] font-bold uppercase" style="color:#3FA46B;">
                                    <span class="material-symbols-outlined text-xs">verified</span>
                                    <span>Master Data Aktif</span>
                                </div>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#E9F5EE; color:#2A7A4C;">
                                <span class="material-symbols-outlined text-xl">package_2</span>
                            </div>
                        </div>

                        <div class="flex-1 bg-white p-5 rounded-xl flex justify-between items-center relative overflow-hidden"
                             style="border:1px solid #E5E3D6; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <div class="absolute left-0 top-0 bottom-0 w-1" style="background: linear-gradient(180deg, #E2A33D, #C1852A);"></div>
                            <div class="pl-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#9CA396;">Total Petugas Kasir</p>
                                <h3 class="text-xl font-bold mt-1 font-display" style="color:#1B241D;"><span class="text-2xl font-extrabold" style="color:#1B241D;">{{ $this->totalAdminTerdaftar() }}</span> Admin</h3>
                                <div class="mt-2 flex items-center gap-1 text-[10px] font-bold uppercase" style="color:#9CA396;">
                                    <span class="material-symbols-outlined text-xs">group</span>
                                    <span>Pengguna Kasir</span>
                                </div>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FBF1DF; color:#C1852A;">
                                <span class="material-symbols-outlined text-xl">groups</span>
                            </div>
                        </div>
                    </div>

                    <!-- DATA LIST: PRODUK TERBARU -->
                    <div class="bg-white rounded-xl overflow-hidden" style="border:1px solid #E5E3D6; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <!-- Header Box Utama -->
                        <div class="px-6 py-4 flex justify-between items-center select-none" style="background:#FAFAF5; border-bottom: 1px solid #E5E3D6;">
                            <div>
                                <h3 class="font-bold text-xs uppercase tracking-wider font-display" style="color:#1B241D;">Produk Baru Ditambahkan</h3>
                                <p class="text-[11px] mt-0.5" style="color:#9CA396;">Daftar produk teranyar yang masuk ke dalam database kasir.</p>
                            </div>
                            <span class="text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider" style="background:#E9F5EE; color:#2A7A4C;">
                                Ringkasan Data
                            </span>
                        </div>

                        <!-- List Content (Flex Table — fixed widths, no CSS-grid dependency) -->
                        <div class="w-full min-w-[600px]">
                            <!-- Header Kolom -->
                            <div class="flex items-center px-6 py-3 text-[11px] font-bold uppercase tracking-wider select-none"
                                 style="background:#F3F4EE; color:#9CA396; border-bottom: 1px solid #E5E3D6;">
                                <div style="width:42%;">Nama Produk</div>
                                <div class="text-center" style="width:25%;">Kode / SKU</div>
                                <div class="text-right" style="width:16%;">Harga Satuan</div>
                                <div class="text-center" style="width:17%;">Stok Sisa</div>
                            </div>

                            <!-- Baris Data Dinamis -->
                            <div class="divide-y" style="border-color:#F0EFE5;">
                                @forelse($this->produkTerbaru() as $produk)
                                    <div class="flex items-center px-6 py-3.5 transition-colors" wire:key="dashboard-prod-{{ $produk->id }}"
                                         onmouseover="this.style.background='#FAFAF5'" onmouseout="this.style.background='transparent'">
                                        <!-- Nama -->
                                        <div class="font-semibold text-xs truncate pr-4" style="width:42%; color:#1B241D;">
                                            {{ $produk->nama_produk }}
                                        </div>
                                        <!-- SKU: signature ticket tag -->
                                        <div class="flex justify-center" style="width:25%;">
                                            <span class="tag-ticket font-mono-data text-[11px] font-semibold" style="background:#F3F4EE; color:#5E6B60;">
                                                {{ $produk->kode_produk ?? '—' }}
                                            </span>
                                        </div>
                                        <!-- Harga -->
                                        <div class="text-right font-bold text-xs font-mono-data" style="width:16%; color:#1B241D;">
                                            Rp {{ number_format($produk->harga, 0, ',', '.') }}
                                        </div>
                                        <!-- Stok -->
                                        <div class="flex justify-center" style="width:17%;">
                                            <span class="tag-ticket text-[11px] font-bold"
                                                  style="{{ $produk->stok > 10 ? 'background:#E9F5EE; color:#2A7A4C;' : 'background:#FBEAE4; color:#C1502F;' }}">
                                                {{ $produk->stok }} pcs
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-12 text-center" style="color:#B8B6A6;" wire:key="dashboard-prod-empty">
                                        <span class="material-symbols-outlined text-3xl block mb-1" style="color:#D3D1C2;">inventory_2</span>
                                        <p class="text-xs font-medium">Belum ada data produk terdaftar.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB PANEL: MASTER DATA PRODUK VIEW -->
                <div x-show="activeTab === 'produk'"
                     class="space-y-4"
                     x-transition:enter="transition ease-out duration-150">

                    <div>
                        <h2 class="text-lg font-bold tracking-tight font-display" style="color:#1B241D;">Manajemen Produk Toko</h2>
                        <p class="text-xs mt-0.5" style="color:#7A8074;">Kelola data sistem aplikasi Maryam Go secara terpusat.</p>
                    </div>

                    <!-- Livewire Container -->
                    <div class="bg-white p-5 rounded-xl" style="border:1px solid #E5E3D6; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div x-show="activeTab === 'produk'">
                            <livewire:pages::admin.data :key="'sub-produk-aktif'" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>