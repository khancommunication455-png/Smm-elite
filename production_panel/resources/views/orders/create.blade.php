@extends('layouts.app')
@section('title', 'New Order')
@section('page-title', 'New Order Wizard')

@section('css')
<style>
.step-item { display:flex; flex-direction:column; align-items:center; gap:4px; }
.step-circle { width:36px; height:36px; border-radius:50%; border:2px solid #424754; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#8c909f; transition:all 0.3s; }
.step-circle.active { border-color:#adc6ff; color:#adc6ff; background:rgba(173,198,255,0.1); box-shadow:0 0 12px rgba(173,198,255,0.3); }
.step-circle.done { border-color:#4edea3; background:#4edea3; color:#003824; }
.step-label { font-size:10px; color:#8c909f; font-weight:600; letter-spacing:.08em; text-transform:uppercase; white-space:nowrap; }
.step-circle.active + .step-label { color:#adc6ff; }
.step-line { flex:1; height:1px; background:#424754; margin-bottom:18px; }
.platform-card { background:rgba(23,31,51,0.5); border:1px solid rgba(173,198,255,0.1); border-radius:12px; padding:14px 10px; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; transition:all 0.2s; }
.platform-card:hover { border-color:#adc6ff; transform:translateY(-2px); box-shadow:0 0 15px rgba(173,198,255,0.15); }
.platform-card.selected { border-color:#adc6ff; background:rgba(173,198,255,0.08); box-shadow:0 0 15px rgba(173,198,255,0.2); }
.platform-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; flex-shrink:0; }
.platform-name { font-size:11px; font-weight:600; color:#8c909f; text-align:center; }
.platform-card.selected .platform-name { color:#adc6ff; }
.svc-row { padding:12px 14px; border-radius:10px; border:1px solid rgba(173,198,255,0.1); cursor:pointer; transition:all 0.15s; margin-bottom:6px; background:rgba(23,31,51,0.3); }
.svc-row:hover { border-color:#adc6ff; background:rgba(173,198,255,0.05); }
.svc-row.selected { border-color:#adc6ff; background:rgba(173,198,255,0.08); }
.step-content { display:none; }
.step-content.active { display:block; }
</style>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">

{{-- Step bar --}}
<div class="flex items-center mb-8">
    @foreach(['Platform','Service','Details','Confirm'] as $i => $label)
    <div class="step-item" id="si-{{ $i+1 }}">
        <div class="step-circle {{ $i===0 ? 'active' : '' }}" id="sc-{{ $i+1 }}">{{ $i+1 }}</div>
        <p class="step-label">{{ $label }}</p>
    </div>
    @if($i < 3)<div class="step-line"></div>@endif
    @endforeach
</div>

{{-- STEP 1: Platform --}}
<div class="step-content active fade-up" id="step-1">
    <div class="glass-card rounded-xl p-md">
        <h2 class="font-h2 text-h2 text-on-surface mb-1">New Order Wizard</h2>
        <p class="text-on-surface-variant text-body-sm mb-6">Choose the platform you want to boost</p>
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
            @php $platforms = [
                ['Instagram','linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)','fab fa-instagram'],
                ['TikTok','linear-gradient(135deg,#010101,#69C9D0)','fab fa-tiktok'],
                ['YouTube','linear-gradient(135deg,#FF0000,#cc0000)','fab fa-youtube'],
                ['Facebook','linear-gradient(135deg,#1877F2,#0A66C2)','fab fa-facebook-f'],
                ['Twitter','linear-gradient(135deg,#1DA1F2,#0d8bd9)','fab fa-twitter'],
                ['Telegram','linear-gradient(135deg,#0088cc,#005a96)','fab fa-telegram'],
                ['Spotify','linear-gradient(135deg,#1DB954,#0f8c3a)','fab fa-spotify'],
                ['Discord','linear-gradient(135deg,#5865F2,#3b4fd4)','fab fa-discord'],
            ]; @endphp
            @foreach($platforms as [$name,$grad,$icon])
            <button class="platform-card" onclick="selectPlatform('{{ $name }}',this)" data-platform="{{ strtolower($name) }}">
                <div class="platform-icon" style="background:{{ $grad }}">
                    <i class="{{ $icon }}"></i>
                </div>
                <span class="platform-name">{{ $name }}</span>
            </button>
            @endforeach
        </div>
    </div>
</div>

{{-- STEP 2: Service --}}
<div class="step-content fade-up" id="step-2">
    <div class="glass-card rounded-xl p-md">
        <div class="flex items-center gap-3 mb-4">
            <button onclick="goStep(1)" class="text-outline hover:text-on-surface transition-colors p-1">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <div>
                <h3 class="font-h3 text-h3 text-on-surface">Choose a service</h3>
                <p class="text-xs text-outline">Platform: <span id="platform-label" class="text-primary font-semibold"></span></p>
            </div>
        </div>
        <input type="text" id="svc-search" placeholder="Search services..." oninput="filterSvc()"
            class="w-full glass-input py-2.5 px-3 mb-4 font-body-sm placeholder:text-outline/50 bg-transparent rounded-lg border border-outline-variant/40 focus:border-primary transition-colors">
        <div class="space-y-2 max-h-80 overflow-y-auto pr-1" id="svc-list">
            @forelse($services as $svc)
            <div class="svc-row" onclick="selectService({{ $svc->id }},'{{ addslashes($svc->name) }}',{{ $svc->rate }},{{ $svc->min }},{{ $svc->max }},'{{ strtolower($svc->category->name ?? '') }}')"
                 data-name="{{ strtolower($svc->name) }}"
                 data-category="{{ strtolower($svc->category->name ?? '') }}">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-on-surface font-medium text-sm truncate">{{ $svc->name }}</p>
                        <p class="text-outline text-xs mt-0.5">Min: {{ number_format($svc->min) }} — Max: {{ number_format($svc->max) }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-primary font-bold font-inter">${{ number_format($svc->rate,4) }}</p>
                        <p class="text-outline text-[10px]">per 1,000</p>
                        <span class="inline-flex mt-1 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                            {{ $svc->rate < 0.5 ? 'bg-tertiary/10 text-tertiary border border-tertiary/30' : ($svc->rate < 1.5 ? 'bg-primary/10 text-primary border border-primary/30' : 'bg-secondary/10 text-secondary border border-secondary/30') }}">
                            {{ $svc->rate < 0.5 ? 'Economy' : ($svc->rate < 1.5 ? 'Standard' : 'Premium') }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-10 text-outline">
                <span class="material-symbols-outlined text-[40px] block mb-2 opacity-30">inventory_2</span>
                <p>No services yet.</p>
                <a href="#" onclick="syncNow()" class="text-primary text-sm hover:underline">Sync from provider →</a>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- STEP 3: Details --}}
<div class="step-content fade-up" id="step-3">
    <div class="glass-card rounded-xl p-md">
        <div class="flex items-center gap-3 mb-6">
            <button onclick="goStep(2)" class="text-outline hover:text-on-surface transition-colors p-1">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <div>
                <h3 class="font-h3 text-h3 text-on-surface">Enter Target Details</h3>
                <p class="text-xs text-outline">Service: <span id="svc-label" class="text-primary font-semibold"></span></p>
            </div>
        </div>

        <div class="space-y-5">
            <div class="space-y-2">
                <label class="font-label-caps text-label-caps text-outline">Link / Target URL *</label>
                <input type="url" id="order-link" class="w-full glass-input py-2.5 px-3 font-body-sm placeholder:text-outline/50 bg-transparent" placeholder="https://...">
                <p class="text-xs text-outline">Must be publicly accessible</p>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <label class="font-label-caps text-label-caps text-outline">Quantity</label>
                    <input type="number" id="qty-num" class="w-24 glass-input py-1.5 px-3 text-sm text-center bg-transparent" oninput="syncSlider()">
                </div>
                <input type="range" id="qty-range" min="100" max="10000" step="100" value="1000"
                    class="w-full accent-blue-400" oninput="syncNum();calcPrice()">
                <div class="flex justify-between text-xs text-outline">
                    <span id="qty-min-label">Min: 100</span>
                    <span id="qty-max-label">Max: 10,000</span>
                </div>
            </div>

            {{-- Price summary --}}
            <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/30">
                <p class="font-label-caps text-label-caps text-outline mb-4">Price Summary</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-outline">Rate / 1K</p>
                        <p class="text-on-surface font-semibold" id="d-rate">$0.0000</p>
                    </div>
                    <div>
                        <p class="text-xs text-outline">Quantity</p>
                        <p class="text-on-surface font-semibold" id="d-qty">1,000</p>
                    </div>
                    <div>
                        <p class="text-xs text-outline">Total USD</p>
                        <p class="text-h2 text-primary neon-text-primary font-bold" style="font-size:24px" id="d-usd">$0.0000</p>
                    </div>
                    <div>
                        <p class="text-xs text-outline">Total PKR</p>
                        <p class="text-h2 text-tertiary font-bold" style="font-size:24px" id="d-pkr">₨0</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-outline-variant/30 flex justify-between text-sm">
                    <span class="text-outline">Balance after order</span>
                    <span id="d-after" class="font-semibold text-on-surface">${{ number_format(auth()->user()->funds ?? 0, 2) }}</span>
                </div>
            </div>

            <button onclick="goStep(4)" class="w-full bg-gradient-primary text-white font-semibold py-3 rounded-lg neon-glow-primary hover:brightness-110 transition-all text-sm">
                Continue to Confirm <span class="material-symbols-outlined text-[16px] align-middle">arrow_forward</span>
            </button>
        </div>
    </div>
</div>

{{-- STEP 4: Confirm + Submit --}}
<div class="step-content fade-up" id="step-4">
    <div class="glass-card rounded-xl p-md">
        <div class="flex items-center gap-3 mb-6">
            <button onclick="goStep(3)" class="text-outline hover:text-on-surface transition-colors p-1">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <h3 class="font-h3 text-h3 text-on-surface">Confirm Order</h3>
        </div>

        <div class="space-y-0 mb-6">
            @foreach([['Platform','r-platform'],['Service','r-service'],['Link','r-link'],['Quantity','r-qty'],['Total (USD)','r-usd'],['Total (PKR)','r-pkr']] as [$label,$id])
            <div class="flex justify-between items-center py-3 border-b border-outline-variant/20">
                <span class="text-outline text-sm">{{ $label }}</span>
                <span id="{{ $id }}" class="text-on-surface font-medium text-sm text-right max-w-[55%] truncate">—</span>
            </div>
            @endforeach
        </div>

        <div class="flex items-start gap-3 bg-[#fcd34d]/10 border border-[#fcd34d]/30 rounded-xl p-4 mb-6 text-[#fcd34d] text-sm">
            <span class="material-symbols-outlined text-[18px] flex-shrink-0 mt-0.5">warning</span>
            <span>Verify the link is correct and publicly visible. Orders cannot be cancelled once processing begins.</span>
        </div>

        <form method="POST" action="{{ route('orders.store') }}" id="order-form">
            @csrf
            <input type="hidden" name="service_id" id="f-service">
            <input type="hidden" name="link" id="f-link">
            <input type="hidden" name="quantity" id="f-quantity">
            <button type="submit" onclick="return prepareSubmit()" class="w-full bg-gradient-primary text-white font-semibold py-3.5 rounded-lg neon-glow-primary hover:brightness-110 transition-all text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">check_circle</span> Place Order
            </button>
        </form>
    </div>
</div>

</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
const PKR = {{ session('usd_pkr_rate', 280) }};
const BAL = {{ auth()->user()->funds ?? 0 }};
let selId=null,selRate=0,selMin=100,selMax=10000,selPlatform='',selName='';

function selectPlatform(name,btn){
    document.querySelectorAll('.platform-card').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected');
    selPlatform=name;
    document.getElementById('platform-label').textContent=name;
    const q=name.toLowerCase();
    document.querySelectorAll('.svc-row').forEach(r=>{
        r.style.display=(r.dataset.category.includes(q)||r.dataset.name.includes(q))?'':'none';
    });
    setTimeout(()=>goStep(2),200);
}

function selectService(id,name,rate,min,max,cat){
    document.querySelectorAll('.svc-row').forEach(r=>r.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    selId=id;selRate=parseFloat(rate);selMin=parseInt(min);selMax=parseInt(max);selName=name;
    document.getElementById('svc-label').textContent=name;
    document.getElementById('d-rate').textContent='$'+selRate.toFixed(4);
    const sl=document.getElementById('qty-range');
    sl.min=selMin;sl.max=selMax;sl.step=Math.max(1,Math.floor(selMin));
    sl.value=selMin;
    document.getElementById('qty-num').value=selMin;
    document.getElementById('qty-min-label').textContent='Min: '+selMin.toLocaleString();
    document.getElementById('qty-max-label').textContent='Max: '+selMax.toLocaleString();
    calcPrice();
    setTimeout(()=>goStep(3),200);
}

function filterSvc(){
    const q=document.getElementById('svc-search').value.toLowerCase();
    document.querySelectorAll('.svc-row').forEach(r=>r.style.display=r.dataset.name.includes(q)?'':'none');
}

function syncNum(){document.getElementById('qty-num').value=document.getElementById('qty-range').value;}
function syncSlider(){
    let v=parseInt(document.getElementById('qty-num').value)||selMin;
    v=Math.min(Math.max(v,selMin),selMax);
    document.getElementById('qty-range').value=v;calcPrice();
}

function calcPrice(){
    const qty=parseInt(document.getElementById('qty-range').value)||selMin;
    const total=(qty/1000)*selRate;
    document.getElementById('d-qty').textContent=qty.toLocaleString();
    document.getElementById('d-usd').textContent='$'+total.toFixed(4);
    document.getElementById('d-pkr').textContent='₨'+Math.round(total*PKR).toLocaleString();
    const after=BAL-total;
    const el=document.getElementById('d-after');
    el.textContent='$'+Math.max(0,after).toFixed(2);
    el.className=after<0?'font-semibold text-error':'font-semibold text-on-surface';
}

function goStep(n){
    if(n===3&&!selId){showToast('Select a service first','warning');return;}
    if(n===4&&!document.getElementById('order-link').value.trim()){showToast('Enter a link','warning');return;}
    for(let i=1;i<=4;i++){
        document.getElementById('step-'+i).classList.toggle('active',i===n);
        const sc=document.getElementById('sc-'+i);
        sc.className='step-circle'+(i===n?' active':i<n?' done':'');
        sc.innerHTML=i<n?'<span class="material-symbols-outlined text-[14px]">check</span>':i;
    }
    if(n===4){
        const qty=document.getElementById('qty-range').value;
        const link=document.getElementById('order-link').value;
        const total=(parseInt(qty)/1000)*selRate;
        document.getElementById('r-platform').textContent=selPlatform;
        document.getElementById('r-service').textContent=selName;
        document.getElementById('r-link').textContent=link;
        document.getElementById('r-qty').textContent=parseInt(qty).toLocaleString();
        document.getElementById('r-usd').textContent='$'+total.toFixed(4);
        document.getElementById('r-pkr').textContent='₨'+Math.round(total*PKR).toLocaleString();
    }
    window.scrollTo({top:0,behavior:'smooth'});
}

function prepareSubmit(){
    const link=document.getElementById('order-link').value.trim();
    if(!selId||!link){showToast('Missing fields','danger');return false;}
    document.getElementById('f-service').value=selId;
    document.getElementById('f-link').value=link;
    document.getElementById('f-quantity').value=document.getElementById('qty-range').value;
    return true;
}

function syncNow(){
   fetch('{{ route("admin.sync.all") }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})
    .then(r=>r.json()).then(d=>{showToast(d.message||'Synced!','success');setTimeout(()=>location.reload(),1500);});
}
</script>
@endsection
