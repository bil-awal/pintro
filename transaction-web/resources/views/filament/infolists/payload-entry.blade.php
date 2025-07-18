<div class="bg-gray-50 rounded-lg p-4">
    <div class="flex items-center justify-between mb-2">
        <h4 class="font-medium text-gray-900">Raw Payload Data</h4>
        <button 
            onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent)"
            class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded transition-colors"
        >
            Copy
        </button>
    </div>
    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs font-mono overflow-x-auto max-h-64">{{ $getState() }}</pre>
</div>
