<x-layouts::app.sidebar>
    <flux:main>

    {{-- HERO SECTION --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50"></div>
        <div class="relative mx-auto max-w-5xl px-6 py-16 text-center sm:px-8 lg:px-12">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-medium text-white/90 backdrop-blur-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Panduan Lengkap
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">WareHouse Koperasi</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-indigo-100">Panduan pengguna lengkap untuk mengelola inventaris gudang, pengambilan barang, pengadaan, dan operasional koperasi Anda.</p>
        </div>
    </div>

    {{-- QUICK NAVIGATION --}}
    <div class="mx-auto max-w-5xl px-6 -mt-8 sm:px-8 lg:px-12">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $quickNav = [
                    ['icon' => 'information-circle', 'label' => 'Pendahuluan', 'href' => '#pendahuluan', 'color' => 'indigo'],
                    ['icon' => 'key', 'label' => 'Login & Akun', 'href' => '#akun', 'color' => 'blue'],
                    ['icon' => 'home', 'label' => 'Dashboard', 'href' => '#dashboard', 'color' => 'emerald'],
                    ['icon' => 'shield-check', 'label' => 'Role & Akses', 'href' => '#roles', 'color' => 'purple'],
                    ['icon' => 'users', 'label' => 'Manajemen Pengguna', 'href' => '#users', 'color' => 'pink'],
                    ['icon' => 'cube', 'label' => 'Inventaris', 'href' => '#inventory', 'color' => 'emerald'],
                    ['icon' => 'shopping-cart', 'label' => 'Pickup', 'href' => '#pickup', 'color' => 'amber'],
                    ['icon' => 'document-duplicate', 'label' => 'Pengadaan', 'href' => '#procurement', 'color' => 'rose'],
                    ['icon' => 'arrow-uturn-left', 'label' => 'Retur', 'href' => '#returns', 'color' => 'violet'],
                    ['icon' => 'chart-bar', 'label' => 'Laporan', 'href' => '#reports', 'color' => 'cyan'],
                    ['icon' => 'bell', 'label' => 'Notifikasi', 'href' => '#notifications', 'color' => 'indigo'],
                    ['icon' => 'cog-6-tooth', 'label' => 'Pengaturan', 'href' => '#settings', 'color' => 'slate'],
                ];
            @endphp
            @foreach($quickNav as $nav)
                <a href="{{ $nav['href'] }}" class="group flex flex-col items-center gap-2 rounded-xl bg-white p-4 shadow-lg shadow-zinc-200/50 transition-all hover:-translate-y-1 hover:shadow-xl dark:bg-zinc-800 dark:shadow-zinc-900/50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-{{ $nav['color'] }}-100 text-{{ $nav['color'] }}-600 transition-colors group-hover:bg-{{ $nav['color'] }}-200 dark:bg-{{ $nav['color'] }}-900/30 dark:text-{{ $nav['color'] }}-400">
                        <flux:icon :name="$nav['icon']" class="h-5 w-5" />
                    </div>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $nav['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mx-auto max-w-5xl space-y-12 px-6 py-12 sm:px-8 lg:px-12">

        {{-- ============================================================ --}}
        {{-- 1. PENDAHULUAN --}}
        {{-- ============================================================ --}}
        <section id="pendahuluan">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon name="information-circle" class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Apa itu WareHouse Koperasi?</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-6 text-zinc-600 dark:text-zinc-300">
                    <strong>WareHouse Koperasi</strong> adalah aplikasi web untuk mengelola stok barang di gudang koperasi desa. Dengan aplikasi ini, Anda bisa:
                </p>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $features = [
                            ['icon' => 'cube', 'title' => 'Inventaris & Stok', 'desc' => 'Kelola daftar barang, cek saldo stok, lihat riwayat pergerakan', 'color' => 'emerald'],
                            ['icon' => 'shopping-cart', 'title' => 'Pengambilan (Pickup)', 'desc' => 'Koperasi anggota bisa minta barang, di-approval oleh Kepala Gudang', 'color' => 'amber'],
                            ['icon' => 'document-duplicate', 'title' => 'Pengadaan (Procurement)', 'desc' => 'Buat purchase request, grouping, PO, penerimaan & QC barang', 'color' => 'rose'],
                            ['icon' => 'arrow-uturn-left', 'title' => 'Retur Barang', 'desc' => 'Ajukan retur, verifikasi, approval, dan penggantian barang', 'color' => 'purple'],
                            ['icon' => 'chart-bar', 'title' => 'Laporan', 'desc' => 'Lihat dan export laporan stok, mutasi, pengadaan, pickup, retur', 'color' => 'blue'],
                            ['icon' => 'bell', 'title' => 'Notifikasi', 'desc' => 'Dapat notifikasi real-time untuk approval dan status berubah', 'color' => 'indigo'],
                        ];
                    @endphp
                    @foreach($features as $feature)
                        <div class="flex gap-3 rounded-xl border border-zinc-100 bg-zinc-50 p-4 transition-colors hover:border-{{ $feature['color'] }}-200 hover:bg-{{ $feature['color'] }}-50/50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-{{ $feature['color'] }}-700 dark:hover:bg-{{ $feature['color'] }}-950/30">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $feature['color'] }}-100 dark:bg-{{ $feature['color'] }}-900/30">
                                <flux:icon :name="$feature['icon']" class="h-4 w-4 text-{{ $feature['color'] }}-600 dark:text-{{ $feature['color'] }}-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $feature['title'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/30 dark:bg-indigo-900/10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                            <flux:icon name="building-office-2" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">Multi-Tenant Berbasis Warehouse</p>
                            <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-400">Setiap pengguna tergabung dalam satu atau lebih warehouse dengan role tertentu. Data dipisah antar warehouse.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 2. PEMBUATAN AKUN & LOGIN --}}
        {{-- ============================================================ --}}
        <section id="akun">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="key" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Login & Pembuatan Akun</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                {{-- Google Login Steps --}}
                <h3 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">Login dengan Google</h3>
                <div class="space-y-4">
                    @php
                        $loginSteps = [
                            ['step' => '1', 'title' => 'Buka Halaman Login', 'desc' => 'Kunjungi halaman login di browser Anda.', 'color' => 'indigo'],
                            ['step' => '2', 'title' => 'Klik "Sign in with Google"', 'desc' => 'Pilih tombol login dengan Google dan pilih akun Anda.', 'color' => 'blue'],
                            ['step' => '3', 'title' => 'Setup Perusahaan (Akun Baru)', 'desc' => 'Masukkan nama dan kode perusahaan, lalu klik "Create".', 'color' => 'purple'],
                            ['step' => '4', 'title' => 'Masuk ke Dashboard', 'desc' => 'Jika akun sudah ada, Anda langsung masuk ke Dashboard.', 'color' => 'emerald'],
                        ];
                    @endphp
                    @foreach($loginSteps as $item)
                        <div class="flex items-start gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-{{ $item['color'] }}-100 text-sm font-bold text-{{ $item['color'] }}-700 dark:bg-{{ $item['color'] }}-900/40 dark:text-{{ $item['color'] }}-300">{{ $item['step'] }}</div>
                            <div class="pt-0.5">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $item['title'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/30 dark:bg-blue-900/10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                            <flux:icon name="exclamation-circle" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-300">Catatan Penting</p>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-400">Tidak ada pendaftaran publik. Akun pertama sebuah warehouse dibuat lewat Google Sign-In, akun berikutnya dibuatkan langsung oleh App Admin/Super Admin (nama, email, dan password) di menu Manajemen Pengguna.</p>
                        </div>
                    </div>
                </div>

                {{-- Multi-Warehouse --}}
                <div class="mt-6 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                            <flux:icon name="building-office-2" class="h-4 w-4 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Multi-Warehouse</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Jika Anda tergabung di beberapa warehouse, setelah login Anda akan diminta memilih warehouse yang ingin digunakan.</p>
                        </div>
                    </div>
                </div>

                {{-- Sesi Login Kedaluwarsa --}}
                <div class="mt-6 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                            <flux:icon name="clock" class="h-4 w-4 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Sesi Login Google Kedaluwarsa</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Jika muncul pesan sesi tidak valid setelah klik "Sign in with Google", cukup ulangi klik tombolnya. Ini bisa terjadi jika halaman login sudah terbuka lama sebelum tombolnya diklik.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 3. DASHBOARD --}}
        {{-- ============================================================ --}}
        <section id="dashboard">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="home" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Dashboard</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-300">Dashboard menampilkan ringkasan data sesuai role Anda. Berikut yang ditampilkan untuk masing-masing role:</p>

                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $dashboards = [
                            ['role' => 'Super Admin', 'icon' => 'shield-check', 'items' => 'Warehouse aktif, cakupan admin, total pengguna', 'color' => 'red'],
                            ['role' => 'App Admin', 'icon' => 'cog-6-tooth', 'items' => 'Pengguna aktif, distribusi role, stok kritis', 'color' => 'orange'],
                            ['role' => 'Kepala Gudang', 'icon' => 'user-group', 'items' => 'Approval pending, stok kritis, backorder', 'color' => 'amber'],
                            ['role' => 'Staff Admin', 'icon' => 'wrench', 'items' => 'Tugas pickup, QC pending, verifikasi retur', 'color' => 'blue'],
                            ['role' => 'Purchasing', 'icon' => 'truck', 'items' => 'Item perlu perhatian, goods receipt terbaru', 'color' => 'purple'],
                            ['role' => 'Koperasi', 'icon' => 'shopping-bag', 'items' => 'Pickup siap ambil, status terakhir, kotak masuk', 'color' => 'emerald'],
                        ];
                    @endphp
                    @foreach($dashboards as $d)
                        <div class="flex gap-3 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $d['color'] }}-100 dark:bg-{{ $d['color'] }}-900/30">
                                <flux:icon :name="$d['icon']" class="h-4 w-4 text-{{ $d['color'] }}-600 dark:text-{{ $d['color'] }}-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $d['role'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $d['items'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 4. ROLE & HAK AKSES --}}
        {{-- ============================================================ --}}
        <section id="roles">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                    <flux:icon name="shield-check" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Role & Hak Akses</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-300">Sistem memiliki 5 role per-warehouse, ditambah 1 hak akses khusus admin platform (Super Admin) yang berlaku lintas warehouse:</p>

                {{-- Role Cards --}}
                <div class="space-y-3">
                    @php
                        $roles = [
                            ['code' => 'super_admin', 'name' => 'Super Admin', 'desc' => 'Pemilik platform. Mengelola semua tenant, support pengguna, memantau kesehatan sistem.', 'color' => 'red', 'icon' => 'shield-check', 'level' => 'Platform Owner'],
                            ['code' => 'app_admin', 'name' => 'App Admin', 'desc' => 'Administrator warehouse. Mengelola pengguna, role, dan pengaturan untuk warehouse-nya.', 'color' => 'orange', 'icon' => 'cog-6-tooth', 'level' => 'Warehouse Admin'],
                            ['code' => 'kepala_gudang', 'name' => 'Kepala Gudang', 'desc' => 'Approver. Menyetujui/menolak pembelian, pengeluaran, retur, dan pickup.', 'color' => 'amber', 'icon' => 'user-group', 'level' => 'Decision Maker'],
                            ['code' => 'staff_admin', 'name' => 'Staff Admin', 'desc' => 'Operator. Mengelola stok, membuat purchase request, verifikasi retur, QC, menyiapkan pickup.', 'color' => 'blue', 'icon' => 'wrench', 'level' => 'Operator'],
                            ['code' => 'purchasing', 'name' => 'Purchasing', 'desc' => 'Koordinator pengadaan. Mengelola PO, grouping request, mencatat penerimaan, mengelola supplier.', 'color' => 'purple', 'icon' => 'truck', 'level' => 'Procurement'],
                            ['code' => 'koperasi', 'name' => 'Koperasi', 'desc' => 'Pelanggan. Membuat permintaan pickup, mengajukan retur, melihat status permintaan.', 'color' => 'emerald', 'icon' => 'shopping-bag', 'level' => 'Customer'],
                        ];
                    @endphp
                    @foreach($roles as $role)
                        <div class="flex items-start gap-4 rounded-xl border border-zinc-100 bg-zinc-50 p-4 transition-colors hover:border-{{ $role['color'] }}-200 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-{{ $role['color'] }}-700 dark:hover:bg-{{ $role['color'] }}-950/20">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $role['color'] }}-100 dark:bg-{{ $role['color'] }}-900/30">
                                <flux:icon :name="$role['icon']" class="h-4 w-4 text-{{ $role['color'] }}-600 dark:text-{{ $role['color'] }}-400" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $role['name'] }}</p>
                                    <span class="rounded-md bg-{{ $role['color'] }}-100 px-2 py-0.5 text-[10px] font-bold uppercase text-{{ $role['color'] }}-700 dark:bg-{{ $role['color'] }}-900/30 dark:text-{{ $role['color'] }}-400">{{ $role['level'] }}</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $role['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-[11px] italic text-zinc-400 dark:text-zinc-500">Catatan: Super Admin bukan bagian dari 5 role per-warehouse di atas &mdash; statusnya adalah hak akses platform yang terpisah dari keanggotaan warehouse manapun, sedangkan 5 role lainnya selalu melekat pada satu warehouse tertentu.</p>

                {{-- Permission Matrix --}}
                <h3 class="mt-8 mb-4 text-lg font-bold text-zinc-900 dark:text-white">Ringkasan Hak Akses per Modul</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-3 py-2.5 text-left font-bold text-zinc-700 dark:text-zinc-300">Modul</th>
                                <th class="px-3 py-2.5 text-center font-bold text-red-600 dark:text-red-400">Super Admin</th>
                                <th class="px-3 py-2.5 text-center font-bold text-orange-600 dark:text-orange-400">App Admin</th>
                                <th class="px-3 py-2.5 text-center font-bold text-amber-600 dark:text-amber-400">Kep. Gudang</th>
                                <th class="px-3 py-2.5 text-center font-bold text-blue-600 dark:text-blue-400">Staff Admin</th>
                                <th class="px-3 py-2.5 text-center font-bold text-purple-600 dark:text-purple-400">Purchasing</th>
                                <th class="px-3 py-2.5 text-center font-bold text-emerald-600 dark:text-emerald-400">Koperasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-750">
                            @php
                                $perms = [
                                    ['modul' => 'Dashboard', 'perms' => ['&#10003;','&#10003;','&#10003;','&#10003;','&#10003;','&#10003;']],
                                    ['modul' => 'Manajemen Pengguna', 'perms' => ['&#10003;','&#10003;','&mdash;','&mdash;','&mdash;','&mdash;']],
                                    ['modul' => 'Katalog Barang', 'perms' => ['&#10003;','&#10003;','Lihat','CRUD','Lihat','&mdash;']],
                                    ['modul' => 'Saldo & Transaksi', 'perms' => ['&#10003;','&#10003;','Lihat','CRUD','Lihat','&mdash;']],
                                    ['modul' => 'Lokasi & Rak', 'perms' => ['&#10003;','&#10003;','Lihat','CRUD','&mdash;','&mdash;']],
                                    ['modul' => 'Request Pickup', 'perms' => ['&#10003;','&#10003;','&mdash;','&mdash;','&mdash;','Buat & Lihat']],
                                    ['modul' => 'Approval Pickup', 'perms' => ['&#10003;','&#10003;','Setujui/Tolak','&mdash;','&mdash;','&mdash;']],
                                    ['modul' => 'Penyiapan Pickup', 'perms' => ['&#10003;','&#10003;','&mdash;','Siapkan & Fulfill','&mdash;','&mdash;']],
                                    ['modul' => 'Purchase Request', 'perms' => ['&#10003;','&#10003;','Lihat','Buat & Lihat','Lihat','&mdash;']],
                                    ['modul' => 'Approval Pembelian', 'perms' => ['&#10003;','&#10003;','Setujui/Tolak','&mdash;','&mdash;','&mdash;']],
                                    ['modul' => 'Grouping & PO', 'perms' => ['&#10003;','&#10003;','&mdash;','&mdash;','CRUD','&mdash;']],
                                    ['modul' => 'Receipts & QC', 'perms' => ['&#10003;','&#10003;','&mdash;','QC','Record','&mdash;']],
                                    ['modul' => 'Retur Barang', 'perms' => ['&#10003;','&#10003;','Setujui/Tolak','Verifikasi','&mdash;','Buat & Lihat']],
                                    ['modul' => 'Laporan', 'perms' => ['&mdash;','&mdash;','Lihat & Export','Lihat & Export','Lihat & Export','&mdash;']],
                                ];
                            @endphp
                            @foreach($perms as $p)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-3 py-2 font-medium text-zinc-700 dark:text-zinc-300">{{ $p['modul'] }}</td>
                                    @foreach($p['perms'] as $perm)
                                        <td class="px-3 py-2 text-center">
                                            @if($perm === '&#10003;')
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">&#10003;</span>
                                            @elseif($perm === '&mdash;')
                                                <span class="text-zinc-300 dark:text-zinc-600">&mdash;</span>
                                            @else
                                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $perm }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 5. MANAJEMEN PENGGUNA --}}
        {{-- ============================================================ --}}
        <section id="users">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-100 dark:bg-pink-900/30">
                    <flux:icon name="users" class="h-5 w-5 text-pink-600 dark:text-pink-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Manajemen Pengguna</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">Hanya <strong>Super Admin</strong> dan <strong>App Admin</strong> yang dapat mengelola pengguna.</p>

                <div class="space-y-4">
                    {{-- View Users --}}
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">1</span>
                            Melihat Daftar Pengguna
                        </h4>
                        <ol class="ml-8 space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <li>Klik menu <strong>"Manajemen Pengguna"</strong> di sidebar</li>
                            <li>Daftar pengguna dalam warehouse akan ditampilkan</li>
                            <li>Gunakan kolom pencarian untuk mencari berdasarkan nama atau email</li>
                        </ol>
                    </div>

                    {{-- Create User --}}
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">2</span>
                            Membuat Pengguna Baru
                        </h4>
                        <ol class="ml-8 space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <li>Klik tombol <strong>"Create"</strong> atau <strong>"Tambah Pengguna"</strong></li>
                            <li>Isi form: Nama, Email, Password, Role</li>
                            <li>Klik <strong>"Save"</strong> untuk menyimpan</li>
                        </ol>
                    </div>

                    {{-- Suspend User --}}
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">3</span>
                            Mengubah Status Pengguna
                        </h4>
                        <p class="ml-8 text-xs text-zinc-600 dark:text-zinc-400">Anda dapat mengaktifkan atau menonaktifkan (suspend) pengguna. Pengguna yang di-suspend tidak dapat login.</p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/30 dark:bg-amber-900/10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <flux:icon name="exclamation-triangle" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">Peringatan Penting</p>
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Super Admin tidak dapat memberikan hak akses platform (super_admin) kepada pengguna lain. Hanya bisa dilakukan langsung di database.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 6. INVENTARIS & GUDANG --}}
        {{-- ============================================================ --}}
        <section id="inventory">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="cube" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Inventaris & Gudang</h2>
            </div>

            <div class="space-y-4">
                {{-- Katalog Barang --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">6.1</span>
                        Katalog Barang
                    </h3>
                    <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">Menu ini menampilkan daftar semua barang di warehouse.</p>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white">Search</p>
                            <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">Cari barang berdasarkan nama, kode, atau barcode</p>
                        </div>
                        <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white">Filter</p>
                            <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">Tampilkan barang aktif atau yang sudah diarsipkan</p>
                        </div>
                        <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-xs font-bold text-zinc-900 dark:text-white">Detail</p>
                            <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">Kode, nama, satuan, stok minimum, barcode, saldo</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-3 dark:border-blue-900/30 dark:bg-blue-900/10">
                        <p class="text-xs text-blue-700 dark:text-blue-400"><strong>Tip:</strong> Barang yang diarsipkan tidak muncul di pencarian stok dan permintaan pickup.</p>
                    </div>
                </div>

                {{-- Saldo & Transaksi Stok --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">6.2</span>
                        Saldo & Transaksi Stok
                    </h3>
                    <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">Menampilkan saldo stok semua barang aktif dengan filter: Semua, Kritis (di bawah minimum), Negatif (di bawah nol).</p>

                    <h4 class="mb-2 text-sm font-bold text-zinc-900 dark:text-white">Cara Mencatat Mutasi Stok:</h4>
                    <ol class="space-y-2">
                        @php
                            $mutasiSteps = [
                                'Klik tombol untuk mencatat mutasi di halaman "Saldo & Transaksi Stok"',
                                'Pilih jenis mutasi: Saldo Awal, Penyesuaian Masuk, atau Penyesuaian Keluar',
                                'Scan atau cari barcode barang yang akan dimutasi',
                                'Masukkan jumlah dan alasan mutasi',
                                'Klik "Submit" untuk menyimpan',
                            ];
                        @endphp
                        @foreach($mutasiSteps as $i => $step)
                            <li class="flex items-start gap-3">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ $i + 1 }}</span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Ledger --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">6.3</span>
                        Ledger Transaksi
                    </h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Riwayat lengkap semua pergerakan stok. Menampilkan: waktu, barang, jenis mutasi, jumlah (+/-), sumber, referensi, dan pelaku. Filter berdasarkan barang, jenis mutasi, atau teks pencarian.</p>
                </div>

                {{-- Supplier & Lokasi --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">6.4</span>
                            Master Supplier
                        </h3>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Daftar supplier yang menyuplai barang ke gudang. Klik "Create" untuk menambah supplier baru dengan nama, kontak, email, telepon, dan alamat.</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">6.5</span>
                            Lokasi & Rak
                        </h3>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Pengelolaan lokasi penyimpanan di dalam gudang. Klik "Create" untuk menambah lokasi baru dengan kode, nama, dan deskripsi.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 7. PENGAMBILAN BARANG (PICKUP) --}}
        {{-- ============================================================ --}}
        <section id="pickup">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="shopping-cart" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Pengambilan Barang (Pickup)</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                {{-- Workflow --}}
                <h3 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">Alur Pengambilan</h3>
                <div class="mb-8 flex flex-wrap items-center gap-2">
                    @php
                        $pickupFlow = ['Draft', 'Submitted', 'Checked', 'Prepared', 'Waiting Approval', 'Approved', 'Ready', 'Completed'];
                    @endphp
                    @foreach($pickupFlow as $i => $step)
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $step }}</div>
                            @if(!$loop->last)
                                <svg class="h-4 w-4 shrink-0 text-amber-300 dark:text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mb-6 rounded-lg border border-amber-100 bg-amber-50 p-3 dark:border-amber-900/30 dark:bg-amber-900/10">
                    <p class="text-xs text-amber-700 dark:text-amber-400"><strong>Catatan:</strong> Status bisa berubah menjadi <strong>BACKORDERED</strong> jika stok tidak tersedia, <strong>REJECTED</strong> jika ditolak, atau <strong>CANCELLED</strong> jika dibatalkan sebelum selesai.</p>
                </div>

                {{-- Steps per role --}}
                <div class="space-y-4">
                    {{-- Koperasi --}}
                    <div class="rounded-xl border-l-4 border-amber-400 bg-amber-50/50 p-4 dark:border-amber-500 dark:bg-amber-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-200/50 dark:bg-amber-800/40"><flux:icon name="shopping-bag" class="h-3 w-3 text-amber-700 dark:text-amber-300" /></span>
                            Koperasi &mdash; Membuat Permintaan
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-amber-800 dark:text-amber-300/80">
                            <li>1. Klik menu <strong>"Request Saya"</strong> di sidebar</li>
                            <li>2. Klik tombol <strong>"Buat Request"</strong></li>
                            <li>3. Cari dan tambahkan barang yang ingin diambil</li>
                            <li>4. Masukkan jumlah dan catatan (opsional) untuk setiap barang</li>
                            <li>5. Klik <strong>"Submit"</strong> untuk mengirim permintaan</li>
                        </ol>
                    </div>

                    {{-- Staff Admin --}}
                    <div class="rounded-xl border-l-4 border-blue-400 bg-blue-50/50 p-4 dark:border-blue-500 dark:bg-blue-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-200/50 dark:bg-blue-800/40"><flux:icon name="wrench" class="h-3 w-3 text-blue-700 dark:text-blue-300" /></span>
                            Staff Admin &mdash; Menyiapkan & Memenuhi
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-blue-800 dark:text-blue-300/80">
                            <li>1. Klik menu <strong>"Penyiapan & Pickup"</strong></li>
                            <li>2. Jika stok tersedia: klik <strong>"Mark Ready"</strong></li>
                            <li>3. Jika stok tidak tersedia: status otomatis <strong>BACKORDERED</strong></li>
                            <li>4. Setelah disetujui, klik <strong>"Fulfill"</strong> untuk menandai selesai</li>
                        </ol>
                    </div>

                    {{-- Kepala Gudang --}}
                    <div class="rounded-xl border-l-4 border-amber-500 bg-amber-50/50 p-4 dark:border-amber-500 dark:bg-amber-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-200/50 dark:bg-amber-800/40"><flux:icon name="user-group" class="h-3 w-3 text-amber-700 dark:text-amber-300" /></span>
                            Kepala Gudang &mdash; Menyetujui
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-amber-800 dark:text-amber-300/80">
                            <li>1. Klik menu <strong>"Approval Pickup"</strong></li>
                            <li>2. Lihat daftar permintaan yang menunggu persetujuan</li>
                            <li>3. Klik <strong>"Approve"</strong> atau <strong>"Reject"</strong> (dengan alasan)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 8. PENGADAAN & PEMBELIAN --}}
        {{-- ============================================================ --}}
        <section id="procurement">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-900/30">
                    <flux:icon name="document-duplicate" class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Pengadaan & Pembelian</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                {{-- Workflow --}}
                <h3 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">Alur Pengadaan</h3>
                <div class="mb-8 flex flex-wrap items-center gap-2">
                    @php
                        $procFlow = ['Purchase Request', 'Approval', 'Grouping', 'Purchase Order', 'Send to Supplier', 'Goods Receipt', 'QC', 'Stock Update'];
                    @endphp
                    @foreach($procFlow as $i => $step)
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">{{ $step }}</div>
                            @if(!$loop->last)
                                <svg class="h-4 w-4 shrink-0 text-rose-300 dark:text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Steps per role --}}
                <div class="space-y-4">
                    {{-- Staff Admin --}}
                    <div class="rounded-xl border-l-4 border-blue-400 bg-blue-50/50 p-4 dark:border-blue-500 dark:bg-blue-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-200/50 dark:bg-blue-800/40"><flux:icon name="wrench" class="h-3 w-3 text-blue-700 dark:text-blue-300" /></span>
                            Staff Admin &mdash; Membuat Purchase Request
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-blue-800 dark:text-blue-300/80">
                            <li>1. Klik menu <strong>"Purchase Request"</strong></li>
                            <li>2. Klik <strong>"Create"</strong> dan tambahkan item yang perlu dibeli</li>
                            <li>3. Pilih urgensi: Low, Normal, High, atau Emergency</li>
                            <li>4. Sistem akan memperingatkan jika ada request serupa yang masih in-progress</li>
                            <li>5. Klik <strong>"Submit"</strong> untuk mengirim ke approval</li>
                        </ol>
                    </div>

                    {{-- Kepala Gudang --}}
                    <div class="rounded-xl border-l-4 border-amber-500 bg-amber-50/50 p-4 dark:border-amber-500 dark:bg-amber-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-200/50 dark:bg-amber-800/40"><flux:icon name="user-group" class="h-3 w-3 text-amber-700 dark:text-amber-300" /></span>
                            Kepala Gudang &mdash; Menyetujui Purchase Request
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-amber-800 dark:text-amber-300/80">
                            <li>1. Klik menu <strong>"Approval Procurement"</strong></li>
                            <li>2. Lihat detail item, jumlah, dan informasi stok saat ini</li>
                            <li>3. Klik <strong>"Approve"</strong> atau <strong>"Reject"</strong> (dengan catatan)</li>
                        </ol>
                    </div>

                    {{-- Purchasing --}}
                    <div class="rounded-xl border-l-4 border-purple-400 bg-purple-50/50 p-4 dark:border-purple-500 dark:bg-purple-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-purple-900 dark:text-purple-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-purple-200/50 dark:bg-purple-800/40"><flux:icon name="truck" class="h-3 w-3 text-purple-700 dark:text-purple-300" /></span>
                            Purchasing &mdash; Grouping, PO, & Penerimaan
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-purple-800 dark:text-purple-300/80">
                            <li>1. <strong>Grouping:</strong> Alokasi item dari purchase request yang sudah di-approve</li>
                            <li>2. <strong>Buat PO:</strong> Pilih supplier, masukkan harga satuan, klik "Create Purchase Order"</li>
                            <li>3. <strong>Kirim PO:</strong> Klik "Send to Supplier" di menu "Purchase Orders"</li>
                            <li>4. <strong>Record Receipt:</strong> Setelah barang diterima, klik "Record Receipt" pada PO</li>
                        </ol>
                    </div>

                    {{-- Staff Admin QC --}}
                    <div class="rounded-xl border-l-4 border-blue-400 bg-blue-50/50 p-4 dark:border-blue-500 dark:bg-blue-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-200/50 dark:bg-blue-800/40"><flux:icon name="magnifying-glass-circle" class="h-3 w-3 text-blue-700 dark:text-blue-300" /></span>
                            Staff Admin &mdash; Quality Control (QC)
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-blue-800 dark:text-blue-300/80">
                            <li>1. Klik menu <strong>"Antrean QC"</strong></li>
                            <li>2. Pilih receipt yang menunggu inspeksi</li>
                            <li>3. Untuk setiap item: pilih Result (PASS/FAIL), Condition, Notes, Evidence</li>
                            <li>4. Jika <strong>PASS</strong>: stok barang otomatis bertambah</li>
                            <li>5. Jika <strong>FAIL</strong>: stok tidak masuk, barang perlu ditindaklanjuti</li>
                        </ol>
                    </div>

                    {{-- Cancellation Request --}}
                    <div class="rounded-xl border-l-4 border-rose-400 bg-rose-50/50 p-4 dark:border-rose-500 dark:bg-rose-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-rose-900 dark:text-rose-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-rose-200/50 dark:bg-rose-800/40"><flux:icon name="x-circle" class="h-3 w-3 text-rose-700 dark:text-rose-300" /></span>
                            Pembatalan Purchase Request (Cancellation Request)
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-rose-800 dark:text-rose-300/80">
                            <li>1. Selama PO belum dikirim ke supplier, pembuat request bisa membuka halaman detail purchase request dan klik <strong>"Request Cancellation"</strong></li>
                            <li>2. Kepala Gudang meninjau permintaan pembatalan di tab <strong>"Cancellations"</strong> pada menu <strong>"Approval Procurement"</strong></li>
                            <li>3. Klik <strong>"Approve"</strong> untuk membatalkan purchase request, atau <strong>"Reject"</strong> agar prosesnya tetap lanjut</li>
                        </ol>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/30 dark:bg-red-900/10">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                            <flux:icon name="exclamation-triangle" class="h-4 w-4 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-red-900 dark:text-red-300">Perhatian</p>
                            <p class="mt-1 text-xs text-red-700 dark:text-red-400">Setelah PO dikirim ke supplier, purchase request tidak dapat diajukan pembatalan lagi &mdash; sebelum itu, pembatalan tetap memerlukan persetujuan Kepala Gudang.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 9. RETUR BARANG --}}
        {{-- ============================================================ --}}
        <section id="returns">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30">
                    <flux:icon name="arrow-uturn-left" class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Retur Barang</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">

                {{-- Workflow --}}
                <h3 class="mb-4 text-lg font-bold text-zinc-900 dark:text-white">Alur Retur</h3>
                <div class="mb-8 flex flex-wrap items-center gap-2">
                    @php
                        $returFlow = ['Submitted', 'Admin Verified', 'Waiting Approval', 'Approved', 'Replacement Pending', 'Ready for Repickup', 'Completed'];
                    @endphp
                    @foreach($returFlow as $i => $step)
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-violet-100 px-3 py-1.5 text-xs font-bold text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">{{ $step }}</div>
                            @if(!$loop->last)
                                <svg class="h-4 w-4 shrink-0 text-violet-300 dark:text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mb-6 rounded-lg border border-violet-100 bg-violet-50 p-3 dark:border-violet-900/30 dark:bg-violet-900/10">
                    <p class="text-xs text-violet-700 dark:text-violet-400"><strong>Catatan:</strong> Status bisa berubah menjadi <strong>REJECTED</strong> jika ditolak oleh Kepala Gudang.</p>
                </div>

                {{-- Steps per role --}}
                <div class="space-y-4">
                    {{-- Koperasi --}}
                    <div class="rounded-xl border-l-4 border-amber-400 bg-amber-50/50 p-4 dark:border-amber-500 dark:bg-amber-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-200/50 dark:bg-amber-800/40"><flux:icon name="shopping-bag" class="h-3 w-3 text-amber-700 dark:text-amber-300" /></span>
                            Koperasi &mdash; Mengajukan Retur
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-amber-800 dark:text-amber-300/80">
                            <li>1. Klik menu <strong>"Retur Barang"</strong></li>
                            <li>2. Klik <strong>"Buat Retur"</strong> dan pilih item dari pickup yang sudah selesai</li>
                            <li>3. Masukkan jumlah yang ingin diretur</li>
                            <li>4. Pilih alasan: Barang Rusak, Cacat, Salah Kirim, atau Lainnya</li>
                            <li>5. <strong>Upload foto bukti</strong> (wajib, max 5MB, jpg/jpeg/png)</li>
                            <li>6. Review di halaman konfirmasi, lalu klik <strong>"Submit"</strong></li>
                        </ol>
                    </div>

                    {{-- Staff Admin --}}
                    <div class="rounded-xl border-l-4 border-blue-400 bg-blue-50/50 p-4 dark:border-blue-500 dark:bg-blue-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-200/50 dark:bg-blue-800/40"><flux:icon name="wrench" class="h-3 w-3 text-blue-700 dark:text-blue-300" /></span>
                            Staff Admin &mdash; Memverifikasi Retur
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-blue-800 dark:text-blue-300/80">
                            <li>1. Klik menu <strong>"Verifikasi Retur"</strong></li>
                            <li>2. Pilih retur yang berstatus <strong>SUBMITTED</strong></li>
                            <li>3. Scan barcode barang untuk memverifikasi</li>
                            <li>4. Masukkan jumlah yang diverifikasi dan upload foto kondisi barang</li>
                            <li>5. Klik <strong>"Verify"</strong>, lalu <strong>"Submit for Approval"</strong></li>
                        </ol>
                    </div>

                    {{-- Kepala Gudang --}}
                    <div class="rounded-xl border-l-4 border-amber-500 bg-amber-50/50 p-4 dark:border-amber-500 dark:bg-amber-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-amber-200/50 dark:bg-amber-800/40"><flux:icon name="user-group" class="h-3 w-3 text-amber-700 dark:text-amber-300" /></span>
                            Kepala Gudang &mdash; Menyetujui Retur
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-amber-800 dark:text-amber-300/80">
                            <li>1. Klik menu <strong>"Keputusan Retur"</strong></li>
                            <li>2. Lihat detail retur: bukti foto, hasil verifikasi, asal kesalahan</li>
                            <li>3. Klik <strong>"Approve"</strong> atau <strong>"Reject"</strong> (dengan alasan)</li>
                        </ol>
                    </div>

                    {{-- Penggantian --}}
                    <div class="rounded-xl border-l-4 border-emerald-400 bg-emerald-50/50 p-4 dark:border-emerald-500 dark:bg-emerald-950/30">
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-emerald-900 dark:text-emerald-200">
                            <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-200/50 dark:bg-emerald-800/40"><flux:icon name="arrow-path" class="h-3 w-3 text-emerald-700 dark:text-emerald-300" /></span>
                            Penggantian Barang (Otomatis)
                        </h4>
                        <ol class="ml-5 space-y-1 text-xs text-emerald-800 dark:text-emerald-300/80">
                            <li>1. Barang lama otomatis didisposisi (stok tidak masuk kembali)</li>
                            <li>2. Sistem memeriksa ketersediaan stok pengganti</li>
                            <li>3. Jika stok tersedia: pickup pengganti langsung dibuat</li>
                            <li>4. Jika stok tidak tersedia: purchase request baru dibuat terlebih dahulu</li>
                            <li>5. Staff Admin menyelesaikan serah terima di menu <strong>"Tugas Penggantian Retur"</strong></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 10. LAPORAN --}}
        {{-- ============================================================ --}}
        <section id="reports">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100 dark:bg-cyan-900/30">
                    <flux:icon name="chart-bar" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">Menu laporan tersedia untuk Kepala Gudang, Staff Admin, dan Purchasing.</p>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $reports = [
                            ['icon' => 'cube', 'name' => 'Stok', 'desc' => 'Daftar barang dengan saldo, stok minimum, status kritis'],
                            ['icon' => 'arrow-path', 'name' => 'Mutasi Stok', 'desc' => 'Riwayat pergerakan stok dengan filter jenis dan tanggal'],
                            ['icon' => 'document-duplicate', 'name' => 'Purchase Request', 'desc' => 'Daftar PR dengan status, urgensi, dan tanggal'],
                            ['icon' => 'clipboard-document-list', 'name' => 'Purchase Order', 'desc' => 'Daftar PO dengan supplier, status, tanggal'],
                            ['icon' => 'shopping-cart', 'name' => 'Pickup', 'desc' => 'Daftar permintaan pickup dengan status dan timeline'],
                            ['icon' => 'arrow-uturn-left', 'name' => 'Retur', 'desc' => 'Daftar retur dengan status, keputusan, penggantian'],
                            ['icon' => 'magnifying-glass-circle', 'name' => 'Penerimaan & QC', 'desc' => 'Daftar penerimaan barang dengan hasil QC'],
                        ];
                    @endphp
                    @foreach($reports as $r)
                        <div class="flex gap-3 rounded-xl border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-900/30">
                                <flux:icon :name="$r['icon']" class="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-zinc-900 dark:text-white">{{ $r['name'] }}</p>
                                <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">{{ $r['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <h4 class="mb-2 text-sm font-bold text-zinc-900 dark:text-white">Cara Mengakses Laporan:</h4>
                    <ol class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                        <li>1. Klik menu <strong>"Laporan"</strong> di sidebar</li>
                        <li>2. Pilih jenis laporan dari dropdown</li>
                        <li>3. Atur filter yang tersedia (status, sumber, barang, rentang tanggal)</li>
                        <li>4. Data akan dimuat secara otomatis</li>
                        <li>5. Klik <strong>"Export"</strong> untuk membuat file laporan dalam format CSV &mdash; prosesnya berjalan di background, status berubah dari "Pending" ke "Completed" sebelum file bisa diunduh</li>
                    </ol>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 11. NOTIFIKASI --}}
        {{-- ============================================================ --}}
        <section id="notifications">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30">
                    <flux:icon name="bell" class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Notifikasi & Kotak Masuk</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30"><flux:icon name="inbox" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" /></span>
                        Kotak Masuk (Inbox)
                    </h3>
                    <ol class="space-y-2">
                        @php
                            $inboxSteps = [
                                'Klik ikon lonceng di sidebar atau header',
                                'Daftar notifikasi akan ditampilkan, dengan yang terbaru di atas',
                                'Filter: All atau Unread',
                                'Klik notifikasi untuk menandai sudah dibaca',
                                'Klik "Mark all as read" untuk menandai semua sudah dibaca',
                            ];
                        @endphp
                        @foreach($inboxSteps as $i => $step)
                            <li class="flex items-start gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">{{ $i + 1 }}</span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="mb-3 flex items-center gap-2 text-lg font-bold text-zinc-900 dark:text-white">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30"><flux:icon name="device-phone-mobile" class="h-4 w-4 text-indigo-600 dark:text-indigo-400" /></span>
                        Push Notification
                    </h3>
                    <p class="mb-3 text-xs text-zinc-600 dark:text-zinc-400">Notifikasi muncul di perangkat Anda secara real-time saat ada:</p>
                    <ul class="space-y-2">
                        @php
                            $pushItems = [
                                ['icon' => 'check-badge', 'text' => 'Permintaan approval (pickup, pembelian, retur)'],
                                ['icon' => 'arrow-path', 'text' => 'Status berubah (pickup siap diambil, PO terkirim)'],
                                ['icon' => 'arrow-uturn-left', 'text' => 'Penggantian barang siap'],
                                ['icon' => 'shield-check', 'text' => 'Alert keamanan'],
                            ];
                        @endphp
                        @foreach($pushItems as $item)
                            <li class="flex items-center gap-2">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-indigo-100 dark:bg-indigo-900/30"><flux:icon :name="$item['icon']" class="h-3 w-3 text-indigo-600 dark:text-indigo-400" /></span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $item['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="sm:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/30 dark:bg-indigo-900/10">
                    <p class="text-xs text-indigo-700 dark:text-indigo-400"><strong>Tip:</strong> Klik notifikasinya (baik di Kotak Masuk maupun push notification) untuk langsung membuka halaman detail yang relevan &mdash; misalnya detail retur, purchase request, atau pickup terkait.</p>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 12. PENGATURAN AKUN --}}
        {{-- ============================================================ --}}
        <section id="settings">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-900/30">
                    <flux:icon name="cog-6-tooth" class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Pengaturan Akun</h2>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-300">Akses pengaturan melalui menu <strong>"Settings"</strong> di dropdown user (klik avatar di pojok kanan atas).</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    @php
                        $settings = [
                            ['icon' => 'user', 'title' => 'Profil', 'items' => ['Ubah nama dan email Anda', 'Perubahan email memerlukan verifikasi ulang'], 'color' => 'blue'],
                            ['icon' => 'key', 'title' => 'Keamanan', 'items' => ['Ubah password', 'Aktifkan 2FA dengan authenticator app', 'Daftar passkey untuk login tanpa password'], 'color' => 'red'],
                            ['icon' => 'paint-brush', 'title' => 'Tampilan', 'items' => ['Light — mode terang', 'Dark — mode gelap', 'System — mengikuti pengaturan OS'], 'color' => 'purple'],
                            ['icon' => 'bell', 'title' => 'Notifikasi', 'items' => ['Kelola preferensi notifikasi per jenis', 'Atur channel notifikasi yang diinginkan'], 'color' => 'emerald'],
                        ];
                    @endphp
                    @foreach($settings as $s)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $s['color'] }}-100 dark:bg-{{ $s['color'] }}-900/30">
                                    <flux:icon :name="$s['icon']" class="h-4 w-4 text-{{ $s['color'] }}-600 dark:text-{{ $s['color'] }}-400" />
                                </div>
                                {{ $s['title'] }}
                            </h4>
                            <ul class="ml-8 space-y-1">
                                @foreach($s['items'] as $item)
                                    <li class="text-xs text-zinc-600 dark:text-zinc-400">{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- TIPS --}}
        {{-- ============================================================ --}}
        <section>
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="light-bulb" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                </div>
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Tips & Catatan Penting</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @php
                    $tips = [
                        ['icon' => 'exclamation-circle', 'title' => 'Stok Kritis', 'desc' => 'Pantau dashboard secara berkala. Barang dengan stok di bawah minimum akan ditandai dengan warna merah.', 'color' => 'red'],
                        ['icon' => 'document-duplicate', 'title' => 'Deteksi Duplikat', 'desc' => 'Sistem akan memperingatkan saat membuat purchase request untuk barang yang sudah ada request in-progress.', 'color' => 'amber'],
                        ['icon' => 'camera', 'title' => 'Bukti Foto', 'desc' => 'Untuk QC dan retur, pastikan foto bukti jelas dan berkualitas baik untuk audit dan penentuan asal kesalahan.', 'color' => 'blue'],
                        ['icon' => 'signal', 'title' => 'Real-time', 'desc' => 'Notifikasi dikirim secara real-time. Pastikan browser mengizinkan notifikasi push.', 'color' => 'emerald'],
                        ['icon' => 'building-office-2', 'title' => 'Multi-Warehouse', 'desc' => 'Perhatikan warehouse yang sedang aktif di header. Semua data dan aksi terkait warehouse aktif.', 'color' => 'purple'],
                        ['icon' => 'arrow-uturn-left', 'title' => 'Retur', 'desc' => 'Foto bukti wajib diupload saat mengajukan retur. Tanpa foto, retur tidak dapat diproses.', 'color' => 'rose'],
                    ];
                @endphp
                @foreach($tips as $tip)
                    <div class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $tip['color'] }}-100 dark:bg-{{ $tip['color'] }}-900/30">
                            <flux:icon :name="$tip['icon']" class="h-4 w-4 text-{{ $tip['color'] }}-600 dark:text-{{ $tip['color'] }}-400" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $tip['title'] }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $tip['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- FOOTER --}}
        <div class="rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-600 p-8 text-center">
            <p class="text-sm font-bold text-white">WareHouse Koperasi</p>
            <p class="mt-1 text-xs text-indigo-100">Dibuat untuk Koperasi Desa Merah Putih &copy; {{ date('Y') }}</p>
        </div>

    </div>

    </flux:main>
</x-layouts::app.sidebar>
