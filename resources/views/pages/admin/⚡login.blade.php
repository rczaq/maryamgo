<?php
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

new class extends Component {
    // State untuk menampung ketikan user
    public $email;
    public $password;
    public $remember = false;

    // Aturan validasi sebelum periksa ke database
    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function prosesLogin()
{
    $this->validate();

    if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
        session()->regenerate();

        // Akun nonaktif tidak boleh masuk
        if (Auth::user()->status === 'nonaktif') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun admin ini sudah dinonaktifkan.',
            ]);
        }

        session()->flash('pesan', 'Selamat datang kembali, Admin ' . Auth::user()->nama . '!');
        return redirect()->to('/admin/dashboard');
    }

    throw ValidationException::withMessages([
        'email' => 'Email atau password salah, Mang.',
    ]);
}
};
?>

<div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4">
    <div class="w-full max-w-md bg-white p-8 rounded-2xl border border-slate-200 shadow-xl">
        
        <!-- HEADER LOGIN -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black text-slate-800 tracking-wide">🚀 MARYAM GO</h2>
            <p class="text-sm text-slate-500 mt-2">Silakan masuk untuk mengelola toko dan produk</p>
        </div>

        <!-- NOTIFIKASI JIKA TERKENA PROTEKSI DARI DASHBOARD -->
        @if (session()->has('error_auth'))
            <div class="mb-4 p-4 bg-rose-100 text-rose-800 rounded-lg text-xs font-semibold shadow-sm">
                🛑 {{ session('error_auth') }}
            </div>
        @endif

        <!-- FORM LOGIN -->
        <form wire:submit.prevent="prosesLogin" class="space-y-5">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Alamat Email</label>
                <input type="email" wire:model="email" placeholder="nama@email.com" 
                    class="w-full border p-3 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 @error('email') border-rose-500 @enderror">
                @error('email') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Password</label>
                <input type="password" wire:model="password" placeholder="••••••••" 
                    class="w-full border p-3 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- REMEMBER ME (INGAT SAYA) -->
            <div class="flex items-center justify-between text-sm pt-1">
                <label class="flex items-center space-x-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" wire:model="remember" class="rounded text-emerald-600 focus:ring-emerald-500">
                    <span>Ingat Saya di Perangkat Ini</span>
                </label>
            </div>

            <!-- TOMBOL MASUK -->
            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white p-3.5 rounded-xl font-bold text-sm transition shadow-lg mt-2">
                Masuk ke Sistem
            </button>
        </form>
    </div>
</div>