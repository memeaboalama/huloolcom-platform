<header class="bg-white/80 backdrop-blur-lg sticky top-0 z-40 shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">
            <a href="{{ route('educator:home') }}" class="flex items-center gap-2">
                <div class="bg-yellow-400 p-2 rounded-lg">
                    {{-- @include('icons.graduation-cap', ['class' => 'h-6 w-6 text-white']) --}}
                </div>
                <span class="text-xl font-bold text-gray-800">Huloolcom</span>
            </a>
            <nav class="hidden md:flex items-center gap-6">
                <a href="#" class="text-gray-600 hover:text-yellow-500 transition-colors">الخدمات</a>
                <a href="#" class="text-gray-600 hover:text-yellow-500 transition-colors">المزودون</a>
                <a href="#" class="text-gray-600 hover:text-yellow-500 transition-colors">من نحن</a>
            </nav>
            <div class="flex items-center gap-4">
                <span class="font-semibold">{{ auth('educator')->user()->name }}</span>
                <form method="POST" action="{{ route('educator:logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-400 text-white rounded-lg hover:bg-yellow-500 transition-colors text-sm">
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
