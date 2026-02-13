<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Barang - Modern UI</title>
    
    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        [v-cloak] { display: none; }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s;
        }
        .fade-enter-from, .fade-leave-to {
            opacity: 0;
        }
        
        .slide-up-enter-active {
            transition: all 0.3s ease-out;
        }
        .slide-up-enter-from {
            transform: translateY(20px);
            opacity: 0;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" v-cloak>
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-box-open text-blue-600 mr-2"></i>
                        Katalog Barang
                    </h1>
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            {{ filteredItems.length }} dari {{ barang.length }} barang
                        </span>
                        <button @click="showCart = !showCart" class="relative">
                            <i class="fas fa-shopping-cart text-xl text-gray-600"></i>
                            <span v-if="cart.length > 0" 
                                  class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                {{ cart.length }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-2"></i>Cari Barang
                        </label>
                        <input v-model="filters.search" 
                               type="text" 
                               placeholder="Nama atau kode barang..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-folder mr-2"></i>Kategori
                        </label>
                        <select v-model="filters.kategori" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Semua Kategori</option>
                            <option v-for="kat in kategoriList" :key="kat.kategori_id" :value="kat.kategori_id">
                                {{ kat.nama_kategori }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- Availability Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-filter mr-2"></i>Status
                        </label>
                        <select v-model="filters.available" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="all">Semua</option>
                            <option value="available">Tersedia</option>
                            <option value="unavailable">Tidak Tersedia</option>
                        </select>
                    </div>
                </div>
                
                <!-- Active Filters -->
                <div v-if="hasActiveFilters" class="mt-4 flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Filter aktif:</span>
                    <button @click="clearFilters" 
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-times-circle mr-1"></i>Clear All
                    </button>
                </div>
            </div>
            
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                <p class="mt-4 text-gray-600">Memuat data...</p>
            </div>
            
            <!-- Empty State -->
            <div v-else-if="filteredItems.length === 0" class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Tidak ada barang ditemukan</h3>
                <p class="text-gray-500">Coba ubah filter pencarian Anda</p>
            </div>
            
            <!-- Barang Grid -->
            <transition-group v-else name="slide-up" tag="div" 
                              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="item in paginatedItems" :key="item.barang_id" 
                     class="bg-white rounded-lg shadow-sm overflow-hidden card-hover cursor-pointer"
                     @click="viewDetail(item)">
                    
                    <!-- Image -->
                    <div class="relative h-48 bg-gray-200">
                        <img v-if="item.gambar" 
                             :src="'/uploads/barang/' + item.gambar" 
                             :alt="item.nama_barang"
                             class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-box text-4xl text-gray-400"></i>
                        </div>
                        
                        <!-- Badge -->
                        <div class="absolute top-2 right-2">
                            <span v-if="item.jumlah_tersedia > 0" 
                                  class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                                Tersedia
                            </span>
                            <span v-else 
                                  class="px-2 py-1 bg-red-500 text-white text-xs font-semibold rounded-full">
                                Habis
                            </span>
                        </div>
                        
                        <!-- Berbayar Badge -->
                        <div v-if="item.is_berbayar" class="absolute top-2 left-2">
                            <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full">
                                <i class="fas fa-dollar-sign"></i> Berbayar
                            </span>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-1">{{ item.nama_barang }}</h3>
                                <p class="text-xs text-gray-500">{{ item.kode_barang }}</p>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                            {{ item.deskripsi || 'Tidak ada deskripsi' }}
                        </p>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">
                                <i class="fas fa-boxes mr-1"></i>
                                {{ item.jumlah_tersedia }} / {{ item.jumlah_total }}
                            </span>
                            
                            <span v-if="item.is_berbayar" class="font-semibold text-blue-600">
                                {{ formatRupiah(item.harga_sewa_per_hari) }}/hari
                            </span>
                            <span v-else class="font-semibold text-green-600">
                                Gratis
                            </span>
                        </div>
                        
                        <!-- Action Button -->
                        <button v-if="item.jumlah_tersedia > 0"
                                @click.stop="addToCart(item)"
                                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors">
                            <i class="fas fa-plus-circle mr-2"></i>Tambah ke Keranjang
                        </button>
                        <button v-else
                                disabled
                                class="mt-4 w-full bg-gray-300 text-gray-500 py-2 rounded-lg cursor-not-allowed">
                            <i class="fas fa-ban mr-2"></i>Tidak Tersedia
                        </button>
                    </div>
                </div>
            </transition-group>
            
            <!-- Pagination -->
            <div v-if="totalPages > 1" class="mt-8 flex items-center justify-center space-x-2">
                <button @click="currentPage = Math.max(1, currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <span class="px-4 py-2 text-gray-700">
                    Halaman {{ currentPage }} dari {{ totalPages }}
                </span>
                
                <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Cart Sidebar -->
        <transition name="fade">
            <div v-if="showCart" class="fixed inset-0 bg-black bg-opacity-50 z-50" @click="showCart = false">
                <div @click.stop class="absolute right-0 top-0 h-full w-96 bg-white shadow-xl p-6 overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold">Keranjang Peminjaman</h2>
                        <button @click="showCart = false" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div v-if="cart.length === 0" class="text-center py-12">
                        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Keranjang kosong</p>
                    </div>
                    
                    <div v-else>
                        <div v-for="(item, index) in cart" :key="index" class="mb-4 p-4 border rounded-lg">
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-semibold">{{ item.nama_barang }}</h4>
                                <button @click="removeFromCart(index)" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="flex items-center space-x-2 mb-2">
                                <button @click="updateQuantity(index, -1)" 
                                        class="w-8 h-8 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input v-model.number="item.quantity" 
                                       type="number" 
                                       min="1" 
                                       :max="item.jumlah_tersedia"
                                       class="w-16 text-center border rounded py-1">
                                <button @click="updateQuantity(index, 1)" 
                                        class="w-8 h-8 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <div v-if="item.is_berbayar" class="text-sm text-gray-600">
                                {{ formatRupiah(item.harga_sewa_per_hari * item.quantity) }}/hari
                            </div>
                        </div>
                        
                        <button @click="checkout" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg mt-4">
                            <i class="fas fa-check mr-2"></i>Ajukan Peminjaman
                        </button>
                    </div>
                </div>
            </div>
        </transition>
        
        <!-- Detail Modal -->
        <transition name="fade">
            <div v-if="selectedItem" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="selectedItem = null">
                <div @click.stop class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <h2 class="text-2xl font-bold">{{ selectedItem.nama_barang }}</h2>
                            <button @click="selectedItem = null" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div v-if="selectedItem.gambar" class="mb-4">
                            <img :src="'/uploads/barang/' + selectedItem.gambar" 
                                 :alt="selectedItem.nama_barang"
                                 class="w-full h-64 object-cover rounded-lg">
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="font-semibold">Kode Barang:</span> {{ selectedItem.kode_barang }}
                            </div>
                            <div>
                                <span class="font-semibold">Kategori:</span> {{ selectedItem.nama_kategori }}
                            </div>
                            <div>
                                <span class="font-semibold">Kondisi:</span> 
                                <span :class="kondisiClass(selectedItem.kondisi)">{{ selectedItem.kondisi }}</span>
                            </div>
                            <div>
                                <span class="font-semibold">Ketersediaan:</span> 
                                {{ selectedItem.jumlah_tersedia }} / {{ selectedItem.jumlah_total }} unit
                            </div>
                            <div v-if="selectedItem.lokasi">
                                <span class="font-semibold">Lokasi:</span> {{ selectedItem.lokasi }}
                            </div>
                            <div v-if="selectedItem.is_berbayar">
                                <span class="font-semibold">Harga Sewa:</span> {{ formatRupiah(selectedItem.harga_sewa_per_hari) }}/hari
                            </div>
                            <div v-if="selectedItem.deposit > 0">
                                <span class="font-semibold">Deposit:</span> {{ formatRupiah(selectedItem.deposit) }}
                            </div>
                            <div v-if="selectedItem.deskripsi">
                                <span class="font-semibold">Deskripsi:</span>
                                <p class="mt-1 text-gray-600">{{ selectedItem.deskripsi }}</p>
                            </div>
                        </div>
                        
                        <button v-if="selectedItem.jumlah_tersedia > 0"
                                @click="addToCart(selectedItem); selectedItem = null"
                                class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg">
                            <i class="fas fa-plus-circle mr-2"></i>Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
    
    <script>
        const { createApp } = Vue;
        
        createApp({
            data() {
                return {
                    barang: [],
                    kategoriList: [],
                    cart: [],
                    loading: true,
                    showCart: false,
                    selectedItem: null,
                    filters: {
                        search: '',
                        kategori: '',
                        available: 'all'
                    },
                    currentPage: 1,
                    itemsPerPage: 12
                }
            },
            computed: {
                filteredItems() {
                    return this.barang.filter(item => {
                        const matchSearch = !this.filters.search || 
                            item.nama_barang.toLowerCase().includes(this.filters.search.toLowerCase()) ||
                            item.kode_barang.toLowerCase().includes(this.filters.search.toLowerCase());
                        
                        const matchKategori = !this.filters.kategori || 
                            item.kategori_id == this.filters.kategori;
                        
                        const matchAvailable = this.filters.available === 'all' ||
                            (this.filters.available === 'available' && item.jumlah_tersedia > 0) ||
                            (this.filters.available === 'unavailable' && item.jumlah_tersedia === 0);
                        
                        return matchSearch && matchKategori && matchAvailable;
                    });
                },
                paginatedItems() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.filteredItems.slice(start, end);
                },
                totalPages() {
                    return Math.ceil(this.filteredItems.length / this.itemsPerPage);
                },
                hasActiveFilters() {
                    return this.filters.search || this.filters.kategori || this.filters.available !== 'all';
                }
            },
            methods: {
                async fetchData() {
                    try {
                        // Fetch barang
                        const barangRes = await fetch('/api/v1/barang');
                        const barangData = await barangRes.json();
                        this.barang = barangData.data || [];
                        
                        // Fetch kategori
                        const kategoriRes = await fetch('/api/v1/kategori');
                        const kategoriData = await kategoriRes.json();
                        this.kategoriList = kategoriData.data || [];
                        
                        this.loading = false;
                    } catch (error) {
                        console.error('Error fetching data:', error);
                        this.loading = false;
                    }
                },
                addToCart(item) {
                    const existing = this.cart.find(i => i.barang_id === item.barang_id);
                    if (existing) {
                        if (existing.quantity < item.jumlah_tersedia) {
                            existing.quantity++;
                        }
                    } else {
                        this.cart.push({
                            ...item,
                            quantity: 1
                        });
                    }
                    this.showCart = true;
                },
                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },
                updateQuantity(index, delta) {
                    const item = this.cart[index];
                    const newQuantity = item.quantity + delta;
                    if (newQuantity > 0 && newQuantity <= item.jumlah_tersedia) {
                        item.quantity = newQuantity;
                    }
                },
                checkout() {
                    // Redirect to peminjaman form with cart data
                    localStorage.setItem('cart', JSON.stringify(this.cart));
                    window.location.href = '/user/peminjaman/add.php';
                },
                viewDetail(item) {
                    this.selectedItem = item;
                },
                formatRupiah(amount) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
                },
                kondisiClass(kondisi) {
                    const classes = {
                        'Baik': 'text-green-600 font-semibold',
                        'Rusak': 'text-red-600 font-semibold',
                        'Maintenance': 'text-yellow-600 font-semibold',
                        'Hilang': 'text-gray-600 font-semibold'
                    };
                    return classes[kondisi] || '';
                },
                clearFilters() {
                    this.filters = {
                        search: '',
                        kategori: '',
                        available: 'all'
                    };
                    this.currentPage = 1;
                }
            },
            watch: {
                filters: {
                    deep: true,
                    handler() {
                        this.currentPage = 1;
                    }
                }
            },
            mounted() {
                this.fetchData();
            }
        }).mount('#app');
    </script>
</body>
</html>
