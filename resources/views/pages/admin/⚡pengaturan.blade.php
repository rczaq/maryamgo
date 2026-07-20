<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $foto;
    public $foto_lama;

    public function mount()
    {
        // Ambil foto aktif dari tabel settings
        $data = Setting::first();
        $this->foto_lama = $data ? $data->foto : null;
    }

    public function simpanQris()
    {
        $this->validate([
            'foto' => 'required|image|max:2048',
        ], [
            'foto.required' => 'Silakan pilih foto QRIS terlebih dahulu!',
            'foto.image'    => 'File harus berupa gambar (JPG, PNG, WebP).',
            'foto.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        // Hapus file lama di storage jika ada
        if ($this->foto_lama && Storage::disk('public')->exists($this->foto_lama)) {
            Storage::disk('public')->delete($this->foto_lama);
        }

        // Simpan foto baru ke storage/app/public/qris-foto
        $path = $this->foto->store('qris-foto', 'public');

        // Simpan / Update di ID = 1
        Setting::updateOrCreate(
            ['id' => 1],
            ['foto' => $path]
        );

        // Update state tampilan & reset input
        $this->foto_lama = $path;
        $this->reset('foto');

        session()->flash('success', 'Foto QRIS berhasil diperbarui!');
    }

    // Batalkan foto preview yang belum disimpan
    public function cancelPreview()
    {
        $this->reset('foto');
        $this->resetValidation('foto');
    }
};
?>

<div class="min-h-screen bg-[#FDFBF7] p-4 sm:p-8 text-[#2A1810]">
    <!-- CDN Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

    <style> 
        body { font-family: 'Plus Jakarta Sans', sans-serif; } 
    </style>

    <div class="max-w-xl mx-auto">
        
        <!-- Header Card -->
        <div class="bg-white rounded-3xl border border-[#EFE6DC] p-6 sm:p-8 shadow-sm">
            
            <div class="flex items-center gap-3 pb-5 mb-6 border-b border-[#EFE6DC]">
                <div class="w-12 h-12 rounded-2xl bg-[#8C4A27] text-white flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold text-[#2A1810] tracking-tight">Pengaturan QRIS Store</h1>
                    <p class="text-xs text-[#786455] font-medium">Upload dan kelola foto QRIS pembayaran toko Anda.</p>
                </div>
            </div>

            <!-- Notifikasi Berhasil -->
            @if (session()->has('success'))
                <div class="p-4 mb-6 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-2 animate-fadeIn">
                    <span class="material-symbols-outlined text-lg">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form wire:submit="simpanQris" class="space-y-6">
                
                <!-- Display & Preview Area -->
                <div class="bg-[#F5EDE4]/60 p-5 rounded-2xl border border-[#EFE6DC] text-center relative">
                    
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-[#786455] uppercase tracking-wider">Status Gambar</span>
                        
                        @if ($foto)
                            <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full flex items-center gap-1 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                Preview Baru (Belum Disimpan)
                            </span>
                        @elseif ($foto_lama)
                            <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full flex items-center gap-1 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                QRIS Aktif
                            </span>
                        @else
                            <span class="text-[10px] font-bold bg-rose-100 text-rose-800 px-2.5 py-0.5 rounded-full border border-rose-200">
                                Belum Ada QRIS
                            </span>
                        @endif
                    </div>

                    <!-- Frame Foto -->
                    <div class="relative bg-white p-4 rounded-xl border border-[#EFE6DC] inline-block shadow-sm w-full max-w-[240px]">
                        @if ($foto)
                            <img src="{{ $foto->temporaryUrl() }}" class="w-48 h-48 object-contain mx-auto rounded-lg">
                        @elseif ($foto_lama)
                            <img src="{{ asset('storage/' . $foto_lama) }}" class="w-48 h-48 object-contain mx-auto rounded-lg">
                        @else
                            <div class="w-48 h-48 rounded-lg bg-[#FDFBF7] border-2 border-dashed border-[#A39181]/40 flex flex-col items-center justify-center text-[#A39181] p-4 mx-auto">
                                <span class="material-symbols-outlined text-4xl mb-1 opacity-60">qr_code_2</span>
                                <span class="text-[11px] font-medium">Belum ada foto QRIS</span>
                            </div>
                        @endif
                    </div>

                    @if ($foto)
                        <div class="mt-3">
                            <button type="button" wire:click="cancelPreview" 
                                    class="text-[11px] font-bold text-rose-600 hover:text-rose-800 inline-flex items-center gap-1 cursor-pointer transition-colors">
                                <span class="material-symbols-outlined text-sm">cancel</span>
                                <span>Batalkan Preview</span>
                            </button>
                        </div>
                    @endif

                </div>

                <!-- Input Upload File -->
                <div>
                    <label class="block text-xs font-bold text-[#2A1810] mb-2">Pilih File Foto Baru</label>
                    
                    <div class="relative">
                        <input type="file" wire:model="foto" accept="image/*" id="qris-upload"
                               class="w-full text-xs text-[#786455] 
                                      file:mr-4 file:py-2.5 file:px-4 
                                      file:rounded-xl file:border-0 
                                      file:text-xs file:font-bold 
                                      file:bg-[#8C4A27] file:text-white 
                                      hover:file:bg-[#703A1E] 
                                      bg-white border border-[#EFE6DC] rounded-xl p-1 cursor-pointer transition-colors">
                    </div>

                    <!-- State Loading saat Upload -->
                    <div wire:loading wire:target="foto" class="text-xs text-[#8C4A27] font-semibold mt-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base animate-spin">sync</span>
                        <span>Mengunggah dan memproses foto...</span>
                    </div>

                    @error('foto') 
                        <p class="text-rose-600 text-xs font-semibold mt-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                    
                    <p class="text-[11px] text-[#A39181] mt-1.5">
                        Format yang didukung: JPG, PNG, WEBP. Ukuran maksimal 2MB.
                    </p>
                </div>

                <!-- Action Button -->
                <button type="submit" 
                        wire:loading.attr="disabled"
                        wire:target="foto"
                        class="w-full py-3 bg-[#2A1810] hover:bg-[#3D2314] disabled:bg-[#EFE6DC] disabled:text-[#A39181] text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="simpanQris" class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Simpan Foto QRIS</span>
                    </span>
                    <span wire:loading wire:target="simpanQris" class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base animate-spin">sync</span>
                        <span>Menyimpan ke Database...</span>
                    </span>
                </button>

            </form>

        </div>

    </div>
</div>