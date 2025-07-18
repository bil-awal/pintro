<div class="space-y-4">
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3">Payment Gateway Response</h3>
        <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm font-mono">{{ $payload }}</pre>
    </div>
    
    <div class="flex justify-end space-x-2">
        <button 
            onclick="navigator.clipboard.writeText(`{{ $payload }}`)"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
            Copy to Clipboard
        </button>
        <button 
            onclick="window.print()"
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
        >
            Print
        </button>
    </div>
</div>
