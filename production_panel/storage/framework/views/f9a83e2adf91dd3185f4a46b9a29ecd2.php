<?php $__env->startSection('title', 'My Orders'); ?>
<?php $__env->startSection('page-title', 'My Orders'); ?>

<?php $__env->startSection('content'); ?>


<div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-6 fade-up">
    <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-3 top-2.5 text-outline text-[18px] pointer-events-none">search</span>
        <input type="text" id="order-search" placeholder="Search by ID or service..."
            class="w-full bg-transparent glass-input pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant/40 focus:border-primary text-sm transition-colors"
            oninput="filterOrders()">
    </div>
    <div class="flex gap-2 flex-wrap">
        <?php $__currentLoopData = ['all','pending','in progress','completed','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button onclick="filterStatus('<?php echo e($s); ?>')" data-status="<?php echo e($s); ?>"
            class="status-filter-btn px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border transition-all
            <?php echo e($s === 'all' ? 'border-primary/50 bg-primary/10 text-primary' : 'border-outline-variant/30 text-outline hover:text-on-surface hover:bg-white/5'); ?>">
            <?php echo e($s === 'all' ? 'All' : ucfirst($s)); ?>

        </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>


<div class="glass-card rounded-xl overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm" id="orders-table">
            <thead>
                <tr class="border-b border-outline-variant/30">
                    <th class="px-5 py-3 text-left font-label-caps text-label-caps text-outline font-normal">ID</th>
                    <th class="px-5 py-3 text-left font-label-caps text-label-caps text-outline font-normal">Service</th>
                    <th class="px-5 py-3 text-left font-label-caps text-label-caps text-outline font-normal hidden md:table-cell">Link</th>
                    <th class="px-5 py-3 text-right font-label-caps text-label-caps text-outline font-normal">Qty</th>
                    <th class="px-5 py-3 text-right font-label-caps text-label-caps text-outline font-normal hidden sm:table-cell">Remains</th>
                    <th class="px-5 py-3 text-right font-label-caps text-label-caps text-outline font-normal">Cost</th>
                    <th class="px-5 py-3 text-center font-label-caps text-label-caps text-outline font-normal">Status</th>
                    <th class="px-5 py-3 text-right font-label-caps text-label-caps text-outline font-normal hidden md:table-cell">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $s  = strtolower($order->status ?? 'pending');
                    $sc = match(true) {
                        $s === 'completed'                      => 'bg-tertiary/10 text-tertiary border-tertiary/30 shadow-[0_0_8px_rgba(78,222,163,0.15)]',
                        in_array($s,['in progress','processing'])=> 'bg-primary/10 text-primary border-primary/30 animate-pulse',
                        in_array($s,['cancelled','refunded'])   => 'bg-error/10 text-error border-error/30',
                        $s === 'partial'                        => 'bg-secondary/10 text-secondary border-secondary/30',
                        default                                 => 'bg-surface-container-highest text-outline border-outline/30',
                    };
                ?>
                <tr class="order-row border-b border-surface-container-high hover:bg-white/5 transition-colors cursor-pointer"
                    data-status="<?php echo e($s); ?>"
                    data-name="<?php echo e(strtolower($order->service->name ?? '')); ?>"
                    onclick="window.location='<?php echo e(route('orders.show', $order->id)); ?>'">
                    <td class="px-5 py-4 font-mono text-outline text-xs">#<?php echo e($order->id); ?></td>
                    <td class="px-5 py-4">
                        <p class="text-on-surface font-medium text-sm max-w-[160px] truncate"><?php echo e($order->service->name ?? 'N/A'); ?></p>
                    </td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <p class="text-outline text-xs max-w-[120px] truncate"><?php echo e($order->link); ?></p>
                    </td>
                    <td class="px-5 py-4 text-right text-on-surface-variant text-sm"><?php echo e(number_format($order->quantity)); ?></td>
                    <td class="px-5 py-4 text-right text-outline text-sm hidden sm:table-cell"><?php echo e(number_format($order->remains ?? 0)); ?></td>
                    <td class="px-5 py-4 text-right text-primary font-semibold">$<?php echo e(number_format($order->total, 4)); ?></td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex px-2 py-1 rounded border text-[10px] font-bold uppercase tracking-wider <?php echo e($sc); ?>">
                            <?php echo e(ucfirst($order->status ?? 'pending')); ?>

                        </span>
                    </td>
                    <td class="px-5 py-4 text-right text-outline text-xs hidden md:table-cell"><?php echo e($order->created_at->format('d M Y')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center text-outline">
                        <span class="material-symbols-outlined text-[48px] block mb-3 opacity-20">shopping_cart</span>
                        <p class="mb-2">No orders yet</p>
                        <a href="<?php echo e(route('orders.create')); ?>" class="text-primary hover:underline text-sm">Place your first order →</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($orders->hasPages()): ?>
    <div class="px-5 py-4 border-t border-outline-variant/30 flex items-center justify-between">
        <p class="text-xs text-outline">Showing <?php echo e($orders->firstItem()); ?>–<?php echo e($orders->lastItem()); ?> of <?php echo e($orders->total()); ?></p>
        <div class="flex gap-2">
            <?php if($orders->onFirstPage()): ?>
            <span class="px-3 py-1.5 text-xs text-outline border border-outline-variant/30 rounded-lg opacity-40">← Prev</span>
            <?php else: ?>
            <a href="<?php echo e($orders->previousPageUrl()); ?>" class="px-3 py-1.5 text-xs text-on-surface border border-outline-variant/30 rounded-lg hover:bg-white/5 transition-colors">← Prev</a>
            <?php endif; ?>
            <?php if($orders->hasMorePages()): ?>
            <a href="<?php echo e($orders->nextPageUrl()); ?>" class="px-3 py-1.5 text-xs text-on-surface border border-outline-variant/30 rounded-lg hover:bg-white/5 transition-colors">Next →</a>
            <?php else: ?>
            <span class="px-3 py-1.5 text-xs text-outline border border-outline-variant/30 rounded-lg opacity-40">Next →</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
function filterOrders(){
    const q = document.getElementById('order-search').value.toLowerCase();
    document.querySelectorAll('.order-row').forEach(r => {
        const match = r.dataset.name.includes(q) || r.querySelector('td:first-child').textContent.includes(q);
        r.style.display = match ? '' : 'none';
    });
}
function filterStatus(status){
    document.querySelectorAll('.status-filter-btn').forEach(b => {
        const active = b.dataset.status === status;
        b.className = b.className
            .replace('border-primary/50 bg-primary/10 text-primary','')
            .replace('border-outline-variant/30 text-outline hover:text-on-surface hover:bg-white/5','')
            .trim();
        b.className += active
            ? ' border-primary/50 bg-primary/10 text-primary'
            : ' border-outline-variant/30 text-outline hover:text-on-surface hover:bg-white/5';
    });
    document.querySelectorAll('.order-row').forEach(r => {
        r.style.display = (status === 'all' || r.dataset.status === status) ? '' : 'none';
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\kbros\Desktop\production_panel\resources\views/orders/index.blade.php ENDPATH**/ ?>