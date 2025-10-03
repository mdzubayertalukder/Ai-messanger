<x-sidebar-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Webhook Response Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Webhook Response #{{ $webhookResponse->id }}</h3>
                        <a href="{{ route('webhooks.index') }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Basic Information</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Platform</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst($webhookResponse->platform) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                                    <dd class="text-sm text-gray-900">{{ $webhookResponse->event_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($webhookResponse->status === 'verified')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        @elseif($webhookResponse->status === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                {{ ucfirst($webhookResponse->status) }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                                    <dd class="text-sm text-gray-900">{{ $webhookResponse->created_at->format('M d, Y H:i:s') }}</dd>
                                </div>
                                @if($webhookResponse->verified_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Verified At</dt>
                                    <dd class="text-sm text-gray-900">{{ $webhookResponse->verified_at->format('M d, Y H:i:s') }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>

                        <!-- Request Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold text-gray-900 mb-3">Request Information</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                                    <dd class="text-sm text-gray-900">{{ $webhookResponse->ip_address }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">User Agent</dt>
                                    <dd class="text-sm text-gray-900 break-all">{{ $webhookResponse->user_agent ?: 'N/A' }}</dd>
                                </div>
                                @if($webhookResponse->verify_token)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Verify Token</dt>
                                    <dd class="text-sm text-gray-900 break-all">{{ $webhookResponse->verify_token }}</dd>
                                </div>
                                @endif
                                @if($webhookResponse->challenge)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Challenge</dt>
                                    <dd class="text-sm text-gray-900 break-all">{{ $webhookResponse->challenge }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Request Data -->
                    @if($webhookResponse->request_data)
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Request Data</h4>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <pre class="text-sm text-gray-800 whitespace-pre-wrap overflow-x-auto">{{ json_encode($webhookResponse->request_data, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif

                    <!-- Response Data -->
                    @if($webhookResponse->response_data)
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Response Data</h4>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <pre class="text-sm text-gray-800 whitespace-pre-wrap overflow-x-auto">{{ json_encode($webhookResponse->response_data, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif

                    <!-- Raw Data for Debugging -->
                    <div class="mt-6">
                        <details class="bg-gray-50 p-4 rounded-lg">
                            <summary class="text-md font-semibold text-gray-900 cursor-pointer">Raw Data (for debugging)</summary>
                            <div class="mt-3 space-y-4">
                                <div>
                                    <h5 class="text-sm font-medium text-gray-700">Raw Request Data:</h5>
                                    <div class="bg-gray-100 p-2 rounded text-xs">
                                        <pre>{{ $webhookResponse->request_data ? json_encode($webhookResponse->request_data, JSON_PRETTY_PRINT) : 'No request data' }}</pre>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-700">Raw Response Data:</h5>
                                    <div class="bg-gray-100 p-2 rounded text-xs">
                                        <pre>{{ $webhookResponse->response_data ? json_encode($webhookResponse->response_data, JSON_PRETTY_PRINT) : 'No response data' }}</pre>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>