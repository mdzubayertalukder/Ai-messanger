<x-sidebar-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('ChatGPT Integration') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Configure your OpenAI ChatGPT integration</p>
            </div>
            <a href="{{ route('integrations.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                ← Back to Integrations
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Success Message -->
        @if (session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ChatGPT Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">OpenAI ChatGPT Configuration</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Configure your OpenAI API settings for ChatGPT integration</p>
                </div>
            </div>

            <!-- Current Status -->
            @if (!empty($chatgptConfig))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-green-900 dark:text-green-300">✅ ChatGPT is configured</p>
                            <p class="text-sm text-green-700 dark:text-green-400">Model: {{ $chatgptConfig['model'] ?? 'Not set' }}</p>
                            <p class="text-sm text-green-700 dark:text-green-400">Max Tokens: {{ $chatgptConfig['max_tokens'] ?? 'Not set' }}</p>
                            <p class="text-xs text-green-600 dark:text-green-500">Last updated: {{ $chatgptConfig['connected_at'] ?? 'Unknown' }}</p>
                        </div>
                        <button id="testConnection" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            Test Connection
                        </button>
                    </div>
                </div>
            @endif

            <!-- Configuration Form -->
            <form method="POST" action="{{ route('integrations.chatgpt.store') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        OpenAI API Key <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="api_key" 
                           name="api_key" 
                           required
                           value="{{ old('api_key', $chatgptConfig['api_key'] ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white"
                           placeholder="sk-...">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">OpenAI Platform</a>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Model <span class="text-red-500">*</span>
                        </label>
                        <select id="model" 
                                name="model" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white">
                            <option value="gpt-3.5-turbo" {{ old('model', $chatgptConfig['model'] ?? 'gpt-3.5-turbo') == 'gpt-3.5-turbo' ? 'selected' : '' }}>
                                GPT-3.5 Turbo (Recommended)
                            </option>
                            <option value="gpt-4" {{ old('model', $chatgptConfig['model'] ?? '') == 'gpt-4' ? 'selected' : '' }}>
                                GPT-4
                            </option>
                            <option value="gpt-4-turbo-preview" {{ old('model', $chatgptConfig['model'] ?? '') == 'gpt-4-turbo-preview' ? 'selected' : '' }}>
                                GPT-4 Turbo Preview
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Max Tokens <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="max_tokens" 
                               name="max_tokens" 
                               required
                               min="1" 
                               max="4000"
                               value="{{ old('max_tokens', $chatgptConfig['max_tokens'] ?? '1000') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white"
                               placeholder="1000">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Maximum number of tokens to generate (1-4000)
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="flex-1 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        Save Configuration
                    </button>
                    
                    @if (!empty($chatgptConfig))
                        <button type="button" id="testConnectionForm" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            Test Connection
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Custom Prompt Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Custom AI Prompt</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Customize how the AI responds to Facebook Messenger users</p>
                </div>
            </div>

            <form method="POST" action="{{ route('integrations.chatgpt.prompt') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="system_prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        System Prompt
                    </label>
                    <textarea id="system_prompt" 
                              name="system_prompt" 
                              rows="6"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white resize-none"
                              placeholder="Enter your custom prompt here...">{{ old('system_prompt', session('chatgpt_system_prompt', 'You are a helpful AI assistant integrated with Facebook Messenger. Respond in a friendly and helpful manner. Keep responses concise and conversational.')) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        This prompt will guide how the AI responds to all Facebook Messenger conversations. Be specific about the tone, style, and behavior you want.
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Current Prompt:</h4>
                    <p class="text-sm text-gray-700 dark:text-gray-300 italic" id="currentPrompt">
                        {{ session('chatgpt_system_prompt', 'You are a helpful AI assistant integrated with Facebook Messenger. Respond in a friendly and helpful manner. Keep responses concise and conversational.') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="flex-1 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                        Update Prompt
                    </button>
                    
                    <button type="button" id="resetPrompt" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                        Reset to Default
                    </button>
                </div>
            </form>

            <!-- Prompt Examples -->
            <div class="mt-6 border-t border-gray-200 dark:border-gray-600 pt-6">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Example Prompts:</h4>
                <div class="space-y-3">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Customer Support:</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 cursor-pointer hover:text-purple-600 dark:hover:text-purple-400" onclick="setPrompt(this.textContent)">
                            You are a professional customer support representative. Always be polite, helpful, and solution-oriented. If you cannot solve an issue, guide users to contact human support.
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sales Assistant:</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 cursor-pointer hover:text-purple-600 dark:hover:text-purple-400" onclick="setPrompt(this.textContent)">
                            You are a knowledgeable sales assistant. Help customers find the right products, answer questions about features and pricing, and guide them through the purchase process. Be enthusiastic but not pushy.
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Educational Tutor:</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 cursor-pointer hover:text-purple-600 dark:hover:text-purple-400" onclick="setPrompt(this.textContent)">
                            You are a patient and encouraging tutor. Break down complex topics into simple explanations, provide examples, and ask questions to ensure understanding. Adapt your teaching style to the user's level.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div id="testResults" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Connection Test Results</h3>
            <div id="testContent" class="space-y-3">
                <!-- Test results will be populated here -->
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3">Getting Started</h3>
            <div class="space-y-3 text-sm text-blue-800 dark:text-blue-400">
                <div>
                    <strong>1. Get your API Key:</strong>
                    <p>Visit <a href="https://platform.openai.com/api-keys" target="_blank" class="underline hover:no-underline">OpenAI Platform</a> and create a new API key.</p>
                </div>
                <div>
                    <strong>2. Choose your Model:</strong>
                    <p>GPT-3.5 Turbo is recommended for most use cases. GPT-4 provides better quality but costs more.</p>
                </div>
                <div>
                    <strong>3. Set Token Limit:</strong>
                    <p>Higher token limits allow for longer responses but consume more credits. 1000 tokens ≈ 750 words.</p>
                </div>
                <div>
                    <strong>4. Test Connection:</strong>
                    <p>Use the test button to verify your configuration is working correctly.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Test Connection -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testButtons = document.querySelectorAll('#testConnection, #testConnectionForm');
            const testResults = document.getElementById('testResults');
            const testContent = document.getElementById('testContent');

            testButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    testChatGPTConnection();
                });
            });

            function testChatGPTConnection() {
                // Show loading state
                testResults.classList.remove('hidden');
                testContent.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                        <span class="text-gray-600 dark:text-gray-400">Testing connection to ChatGPT...</span>
                    </div>
                `;

                // Make the test request
                fetch('{{ route("integrations.chatgpt.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        testContent.innerHTML = `
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="font-medium text-green-800 dark:text-green-300">Connection Successful!</span>
                                </div>
                                <p class="text-sm text-green-700 dark:text-green-400 mb-2">${data.message}</p>
                                ${data.response ? `<div class="bg-white dark:bg-gray-800 rounded p-3 text-sm"><strong>Test Response:</strong><br>${data.response}</div>` : ''}
                            </div>
                        `;
                    } else {
                        testContent.innerHTML = `
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <span class="font-medium text-red-800 dark:text-red-300">Connection Failed</span>
                                </div>
                                <p class="text-sm text-red-700 dark:text-red-400">${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    testContent.innerHTML = `
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div class="flex items-center space-x-2 mb-2">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="font-medium text-red-800 dark:text-red-300">Test Failed</span>
                            </div>
                            <p class="text-sm text-red-700 dark:text-red-400">An error occurred while testing the connection.</p>
                        </div>
                    `;
                });
            }

            // Prompt management functions
            const resetPromptBtn = document.getElementById('resetPrompt');
            const systemPromptTextarea = document.getElementById('system_prompt');
            const currentPromptDisplay = document.getElementById('currentPrompt');

            if (resetPromptBtn) {
                resetPromptBtn.addEventListener('click', function() {
                    const defaultPrompt = 'You are a helpful AI assistant integrated with Facebook Messenger. Respond in a friendly and helpful manner. Keep responses concise and conversational.';
                    systemPromptTextarea.value = defaultPrompt;
                });
            }
        });

        // Global function for setting prompt from examples
        function setPrompt(promptText) {
            const systemPromptTextarea = document.getElementById('system_prompt');
            if (systemPromptTextarea) {
                systemPromptTextarea.value = promptText.trim();
                systemPromptTextarea.focus();
            }
        }
    </script>
</x-sidebar-layout>