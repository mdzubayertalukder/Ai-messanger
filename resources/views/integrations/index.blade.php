<x-sidebar-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Integrations') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Connect your Facebook pages and WooCommerce stores</p>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Success Message -->
        @if (session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Facebook Integration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Facebook Messenger</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Connect your Facebook page to receive and respond to messages</p>
                    </div>
                </div>
                
                @if ($facebookPages->count() > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                        {{ $facebookPages->count() }} Connected
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                        Not Connected
                    </span>
                @endif
            </div>

            <!-- Connected Facebook Pages -->
            @if ($facebookPages->count() > 0)
                <div class="space-y-3 mb-6">
                    @foreach ($facebookPages as $page)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-blue-900 dark:text-blue-300">{{ $page->page_name }}</p>
                                    <p class="text-sm text-blue-700 dark:text-blue-400">Page ID: {{ $page->page_id }}</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-500">
                                        Status: {{ $page->subscribed ? 'Active' : 'Inactive' }} | 
                                        Connected: {{ $page->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('integrations.facebook.destroy') }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="page_id" value="{{ $page->page_id }}">
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                                        Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($facebookPages->count() == 0)
                <!-- Facebook Connection Form -->
                <form method="POST" action="{{ route('integrations.facebook.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="page_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page Name</label>
                            <input type="text" id="page_name" name="page_name" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="My Business Page">
                        </div>
                        <div>
                            <label for="page_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page ID</label>
                            <input type="text" id="page_id" name="page_id" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="123456789012345">
                        </div>
                    </div>
                    <div>
                        <label for="page_access_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page Access Token</label>
                        <input type="password" id="page_access_token" name="page_access_token" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="Enter your Facebook page access token">
                    </div>
                    <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Connect Facebook Page
                    </button>
                </form>
            @endif
        </div>

        <!-- WooCommerce Integration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.5 9.5c-.3-1.4-1.4-2.4-2.8-2.4h-3.2c.1-.4.1-.8.1-1.2 0-2.2-1.8-4-4-4s-4 1.8-4 4c0 .4 0 .8.1 1.2H6.5c-1.4 0-2.5 1-2.8 2.4L2.5 17c-.2 1.1.6 2 1.7 2h15.6c1.1 0 1.9-.9 1.7-2l-1.2-7.5zM13.6 5.9c1.2 0 2.2 1 2.2 2.2 0 .3 0 .6-.1.9h-4.2c-.1-.3-.1-.6-.1-.9 0-1.2 1-2.2 2.2-2.2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">WooCommerce</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Connect your WooCommerce stores to sync products and orders</p>
                    </div>
                </div>
                
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $wooStores->count() > 0 ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                    {{ $wooStores->count() > 0 ? $wooStores->count() . ' Connected' : 'Not Connected' }}
                </span>
            </div>

            <!-- Connected WooCommerce Stores -->
            @if ($wooStores->count() > 0)
                <div class="space-y-3 mb-6">
                    @foreach ($wooStores as $store)
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-purple-900 dark:text-purple-300">{{ $store->store_name }}</p>
                                    <p class="text-sm text-purple-700 dark:text-purple-400">{{ $store->store_url }}</p>
                                    @if ($store->last_synced_at)
                                        <p class="text-xs text-purple-600 dark:text-purple-500">Last synced: {{ $store->last_synced_at->diffForHumans() }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('integrations.woocommerce.destroy', $store) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                                        Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- WooCommerce Connection Form -->
            <form method="POST" action="{{ route('integrations.woocommerce.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Store Name</label>
                        <input type="text" id="store_name" name="store_name" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white"
                            placeholder="My WooCommerce Store">
                    </div>
                    <div>
                        <label for="store_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Store URL</label>
                        <input type="url" id="store_url" name="store_url" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white"
                            placeholder="https://mystore.com">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="consumer_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Key</label>
                        <input type="text" id="consumer_key" name="consumer_key" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ck_...">
                    </div>
                    <div>
                        <label for="consumer_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Consumer Secret</label>
                        <input type="password" id="consumer_secret" name="consumer_secret" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white"
                            placeholder="cs_...">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Version</label>
                        <select id="version" name="version" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white">
                            <option value="wc/v3">WC/v3 (Recommended)</option>
                            <option value="wc/v2">WC/v2</option>
                            <option value="wc/v1">WC/v1</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="wp_api" name="wp_api" value="1" checked
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="wp_api" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Use WordPress API
                        </label>
                    </div>
                </div>
                <button type="submit" class="w-full md:w-auto px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                    Connect WooCommerce Store
                </button>
            </form>
        </div>

        <!-- ChatGPT Integration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ChatGPT Integration</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Connect to OpenAI's ChatGPT for AI-powered conversations</p>
                    </div>
                </div>
                
                @if (session('chatgpt_integration'))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                        Connected
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                        Not Connected
                    </span>
                @endif
            </div>

            @if (session('chatgpt_integration'))
                <!-- Connected ChatGPT Configuration -->
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-green-900 dark:text-green-300">Model: {{ session('chatgpt_integration.model') }}</p>
                            <p class="text-sm text-green-700 dark:text-green-400">Max Tokens: {{ session('chatgpt_integration.max_tokens') }}</p>
                            <p class="text-xs text-green-600 dark:text-green-500">Connected: {{ session('chatgpt_integration.connected_at') }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('integrations.chatgpt') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Configure
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Configure your ChatGPT integration to enable AI-powered conversations.</p>
                    <a href="{{ route('integrations.chatgpt') }}" class="inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Configure ChatGPT
                    </a>
                </div>
            @endif
        </div>

        <!-- Integration Help -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3">Need Help?</h3>
            <div class="space-y-3 text-sm text-blue-800 dark:text-blue-400">
                <div>
                    <strong>Facebook Integration:</strong>
                    <p>To get your Facebook page access token, visit the Facebook Developers Console and create an app with pages_messaging permission.</p>
                </div>
                <div>
                    <strong>WooCommerce Integration:</strong>
                    <p>Generate API keys in your WooCommerce store: WooCommerce → Settings → Advanced → REST API → Add Key.</p>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>