<div x-data="paymentForm()" class="max-w-lg mx-auto">
    <form @submit.prevent="submitPayment" class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment Details</h2>
        
        <!-- Amount -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">{{ $currencySymbol }}</span>
                <input type="number" x-model="amount" step="0.01" min="0.01" required
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md">
            </div>
        </div>
        
        <!-- Country Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
            <select x-model="country" @change="updateCurrency" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @foreach($countries as $code => $country)
                <option value="{{ $code }}">{{ $country['name'] }}</option>
                @endforeach
            </select>
        </div>
        
        <!-- Customer Email -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" x-model="email" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        
        <!-- Customer Name -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input type="text" x-model="name" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        
        <!-- Customer Phone -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input type="tel" x-model="phone"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        
        <!-- Payment Method (if mobile money available) -->
        <div class="mb-6" x-show="mobileProviders.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" x-model="paymentMethod" value="card" class="mr-2">
                    <span>Credit/Debit Card</span>
                </label>
                <template x-for="provider in mobileProviders">
                    <label class="flex items-center">
                        <input type="radio" x-model="paymentMethod" :value="provider" class="mr-2">
                        <span x-text="provider"></span>
                    </label>
                </template>
            </div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" :disabled="processing"
                class="w-full py-3 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50">
            <span x-show="!processing">Pay Now</span>
            <span x-show="processing">Processing...</span>
        </button>
    </form>
</div>

<script>
function paymentForm() {
    return {
        amount: {{ $amount ?? '0' }},
        country: '{{ $defaultCountry }}',
        currency: '{{ $defaultCurrency }}',
        email: '',
        name: '',
        phone: '',
        paymentMethod: 'card',
        mobileProviders: {!! json_encode($countries[$defaultCountry]['mobile_providers'] ?? []) !!},
        processing: false,
        
        updateCurrency() {
            // Update currency based on selected country
            fetch(`/api/dpo/countries`)
                .then(r => r.json())
                .then(data => {
                    this.currency = data.countries[this.country].currency;
                    this.mobileProviders = data.countries[this.country].mobile_providers || [];
                    this.paymentMethod = 'card';
                });
        },
        
        async submitPayment() {
            this.processing = true;
            
            try {
                const response = await fetch('/api/dpo/payments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        amount: this.amount,
                        country: this.country,
                        currency: this.currency,
                        customer_email: this.email,
                        customer_name: this.name,
                        customer_phone: this.phone,
                        payment_method: this.paymentMethod,
                        description: 'Payment'
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    alert(data.error || 'Payment initialization failed');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                this.processing = false;
            }
        }
    }
}
</script>