@extends('layouts.app')
@section('title', 'Add Funds')
@section('page-title', 'Secure Add Funds')

@section('css')
<style>
.method-card{background:rgba(23,31,51,0.5);border:1px solid rgba(173,198,255,0.1);border-radius:12px;padding:16px;cursor:pointer;transition:all 0.2s;}
.method-card:hover{border-color:#adc6ff;box-shadow:0 0 15px rgba(173,198,255,0.1);}
.method-card.selected{border-color:#adc6ff;background:rgba(173,198,255,0.07);box-shadow:0 0 15px rgba(173,198,255,0.2);}
.method-card .check{opacity:0;transition:opacity 0.2s;}
.method-card.selected .check{opacity:1;}
.quick-btn{padding:8px 16px;border-radius:8px;border:1px solid rgba(173,198,255,0.2);font-size:13px;font-weight:600;color:#8c909f;background:transparent;cursor:pointer;transition:all 0.15s;}
.quick-btn:hover,.quick-btn.active{border-color:#adc6ff;background:rgba(173,198,255,0.08);color:#adc6ff;}
</style>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">

{{-- Current balance hero --}}
<div class="glass-card rounded-xl p-md mb-6 relative overflow-hidden fade-up">
    <div class="absolute inset-0 opacity-10" style="background:linear-gradient(135deg,#4d8eff22,transparent)"></div>
    <div class="relative z-10 flex items-center justify-between">
        <div>
            <p class="font-label-caps text-label-caps text-outline mb-2">Current Balance</p>
            <div class="flex items-baseline gap-6">
                <div>
                    <p class="font-h1 text-on-surface neon-text-primary" style="font-size:40px">${{ number_format(auth()->user()->funds ?? 0, 2) }}</p>
                    <p class="text-xs text-outline mt-1">USD</p>
                </div>
                <div class="border-l border-outline-variant/30 pl-6">
                    <p class="font-h2 text-tertiary" style="font-size:28px">₨{{ number_format((auth()->user()->funds ?? 0) * session('usd_pkr_rate',280), 0) }}</p>
                    <p class="text-xs text-outline mt-1">PKR @ {{ session('usd_pkr_rate',280) }}</p>
                </div>
            </div>
        </div>
        <div class="w-14 h-14 rounded-xl bg-gradient-primary flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-white text-[28px]">account_balance_wallet</span>
        </div>
    </div>
</div>

{{-- Payment method selector --}}
<div class="mb-6 fade-up">
    <h2 class="font-h3 text-h3 text-primary flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined">credit_card</span> Select Payment Method
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="method-grid">
        @php
        $methods = [
            ['id'=>'stripe',    'name'=>'Credit / Debit Card', 'sub'=>'Visa, Mastercard, Amex',  'icon'=>'credit_card',           'color'=>'text-blue-400',   'fee'=>'2.9% + $0.30', 'min'=>'$1',    'max'=>'$10,000', 'currency'=>'USD', 'fee_pct'=>2.9,  'fee_fixed'=>0.30],
            ['id'=>'paypal',    'name'=>'PayPal',               'sub'=>'Secure online payment',   'icon'=>'account_balance',        'color'=>'text-blue-300',   'fee'=>'3.4% + $0.30', 'min'=>'$1',    'max'=>'$5,000',  'currency'=>'USD', 'fee_pct'=>3.4,  'fee_fixed'=>0.30],
            ['id'=>'easypaisa', 'name'=>'EasyPaisa',            'sub'=>'Mobile wallet Pakistan',  'icon'=>'smartphone',             'color'=>'text-green-400',  'fee'=>'1.5%',         'min'=>'₨500',  'max'=>'₨100K',   'currency'=>'PKR', 'fee_pct'=>1.5,  'fee_fixed'=>0],
            ['id'=>'jazzcash',  'name'=>'JazzCash',             'sub'=>'Mobile wallet Pakistan',  'icon'=>'sim_card',               'color'=>'text-red-400',    'fee'=>'1.5%',         'min'=>'₨500',  'max'=>'₨100K',   'currency'=>'PKR', 'fee_pct'=>1.5,  'fee_fixed'=>0],
            ['id'=>'crypto',    'name'=>'USDT / Crypto',        'sub'=>'TRC20, BEP20 networks',   'icon'=>'currency_bitcoin',       'color'=>'text-yellow-400', 'fee'=>'Free',         'min'=>'$5',    'max'=>'$50,000', 'currency'=>'USD', 'fee_pct'=>0,    'fee_fixed'=>0],
            ['id'=>'pm',        'name'=>'Perfect Money',        'sub'=>'E-wallet payment',        'icon'=>'payments',               'color'=>'text-purple-400', 'fee'=>'Free',         'min'=>'$1',    'max'=>'$50,000', 'currency'=>'USD', 'fee_pct'=>0,    'fee_fixed'=>0],
        ];
        @endphp
        @foreach($methods as $m)
        <button class="method-card text-left"
            onclick="selectMethod('{{ $m['id'] }}')"
            data-id="{{ $m['id'] }}"
            data-fee-pct="{{ $m['fee_pct'] }}"
            data-fee-fixed="{{ $m['fee_fixed'] }}"
            data-currency="{{ $m['currency'] }}">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined {{ $m['color'] }} text-[24px]">{{ $m['icon'] }}</span>
                    <div>
                        <p class="text-on-surface font-semibold text-sm">{{ $m['name'] }}</p>
                        <p class="text-outline text-xs">{{ $m['sub'] }}</p>
                    </div>
                </div>
                <div class="check w-5 h-5 rounded-full bg-gradient-primary flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white text-[12px]">check</span>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs text-outline border-t border-outline-variant/20 pt-2 mt-1">
                <div><span class="block text-on-surface-variant font-medium">Fee</span>{{ $m['fee'] }}</div>
                <div><span class="block text-on-surface-variant font-medium">Min</span>{{ $m['min'] }}</div>
                <div><span class="block text-on-surface-variant font-medium">Max</span>{{ $m['max'] }}</div>
            </div>
        </button>
        @endforeach
    </div>
</div>

{{-- Amount section --}}
<div id="amount-section" class="glass-card rounded-xl p-md mb-6 fade-up" style="display:none">
    <h3 class="font-h3 text-h3 text-on-surface mb-5">Deposit Details</h3>

    {{-- Quick amounts --}}
    <div class="flex flex-wrap gap-2 mb-4" id="quick-amounts"></div>

    <div class="relative mb-5">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-outline font-bold" id="currency-symbol">$</span>
        <input type="number" id="amount-input" placeholder="0.00" min="1" step="0.01"
            class="w-full glass-input py-3 pl-8 pr-4 text-xl font-bold bg-transparent rounded-lg border border-outline-variant/40 focus:border-primary transition-colors"
            oninput="calcFee()">
    </div>

    {{-- Fee breakdown --}}
    <div class="bg-surface-container-low rounded-xl p-4 space-y-2 border border-outline-variant/30 mb-5">
        <div class="flex justify-between text-sm">
            <span class="text-outline">You pay</span>
            <span class="text-on-surface font-semibold" id="calc-pay">—</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-outline">Processing fee</span>
            <span class="text-error font-semibold" id="calc-fee">—</span>
        </div>
        <div class="border-t border-outline-variant/30 pt-2 flex justify-between">
            <span class="text-on-surface-variant font-semibold text-sm">Wallet credit (USD)</span>
            <span class="text-primary font-bold text-lg neon-text-primary" id="calc-usd">—</span>
        </div>
        <div class="flex justify-between">
            <span class="text-on-surface-variant font-semibold text-sm">Wallet credit (PKR)</span>
            <span class="text-tertiary font-bold text-lg" id="calc-pkr">—</span>
        </div>
    </div>

    {{-- Payment forms --}}
    <form id="form-stripe" method="POST" action="{{ route('funds.stripe') }}">
        @csrf <input type="hidden" name="amount" id="stripe-amount">
    </form>
    <form id="form-paypal" method="POST" action="{{ route('funds.paypal') }}">
        @csrf <input type="hidden" name="amount" id="paypal-amount">
    </form>

    <button onclick="proceedPayment()" class="w-full bg-gradient-primary text-white font-semibold py-3.5 rounded-lg neon-glow-primary hover:brightness-110 transition-all flex items-center justify-center gap-2 text-sm">
        <span class="material-symbols-outlined text-[18px]">lock</span> Proceed to Payment
    </button>

    <p class="text-center text-xs text-outline mt-3 flex items-center justify-center gap-1">
        <span class="material-symbols-outlined text-[14px]">security</span>
        256-bit SSL encrypted · Funds credited instantly
    </p>
</div>

</div>
@endsection

@section('scripts')
<script>
const PKR = {{ session('usd_pkr_rate', 280) }};
let activeMethod = null;
const quickUSD = [5,10,25,50,100,250];
const quickPKR = [500,1000,2500,5000,10000,25000];

function selectMethod(id){
    document.querySelectorAll('.method-card').forEach(c=>c.classList.remove('selected'));
    const card=document.querySelector(`[data-id="${id}"]`);
    card.classList.add('selected');
    activeMethod={id,feePct:parseFloat(card.dataset.feePct),feeFixed:parseFloat(card.dataset.feeFixed),currency:card.dataset.currency};
    const isPkr=activeMethod.currency==='PKR';
    document.getElementById('currency-symbol').textContent=isPkr?'₨':'$';
    document.getElementById('amount-input').value='';
    ['calc-pay','calc-fee','calc-usd','calc-pkr'].forEach(i=>document.getElementById(i).textContent='—');
    const qa=document.getElementById('quick-amounts');
    qa.innerHTML='';
    (isPkr?quickPKR:quickUSD).forEach(v=>{
        const b=document.createElement('button');
        b.className='quick-btn';
        b.textContent=(isPkr?'₨':'$')+v.toLocaleString();
        b.onclick=()=>{
            document.getElementById('amount-input').value=v;
            document.querySelectorAll('.quick-btn').forEach(x=>x.classList.remove('active'));
            b.classList.add('active');calcFee();
        };
        qa.appendChild(b);
    });
    document.getElementById('amount-section').style.display='block';
}

function calcFee(){
    if(!activeMethod)return;
    const raw=parseFloat(document.getElementById('amount-input').value)||0;
    if(raw<=0){['calc-pay','calc-fee','calc-usd','calc-pkr'].forEach(i=>document.getElementById(i).textContent='—');return;}
    const isPkr=activeMethod.currency==='PKR';
    const sym=isPkr?'₨':'$';
    const fee=(raw*activeMethod.feePct/100)+activeMethod.feeFixed;
    const net=raw-fee;
    const netUsd=isPkr?net/PKR:net;
    document.getElementById('calc-pay').textContent=sym+raw.toFixed(2);
    document.getElementById('calc-fee').textContent='-'+sym+fee.toFixed(2);
    document.getElementById('calc-usd').textContent='$'+netUsd.toFixed(4);
    document.getElementById('calc-pkr').textContent='₨'+Math.round(netUsd*PKR).toLocaleString();
}

function proceedPayment(){
    if(!activeMethod){showToast('Select a payment method','warning');return;}
    const amount=parseFloat(document.getElementById('amount-input').value);
    if(!amount||amount<=0){showToast('Enter a valid amount','warning');return;}
    if(activeMethod.id==='stripe'){document.getElementById('stripe-amount').value=amount;document.getElementById('form-stripe').submit();}
    else if(activeMethod.id==='paypal'){document.getElementById('paypal-amount').value=amount;document.getElementById('form-paypal').submit();}
    else showToast('Manual payment — please contact support with reference: '+activeMethod.id.toUpperCase()+'-'+Date.now(),'info');
}
</script>
@endsection
