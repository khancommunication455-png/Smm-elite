

<?php $__env->startSection('title', 'Create API Provider'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex-1 p-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-on-surface">Create API Provider</h1>
            <p class="text-on-surface-variant mt-1">Add a new social media API provider</p>
        </div>

        <div class="glass-card p-6 rounded-xl">
            <form action="<?php echo e(route('admin.providers.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-on-surface mb-2">Provider Name</label>
                        <input type="text" id="name" name="name" value="<?php echo e(old('name')); ?>"
                               class="glass-input w-full" placeholder="e.g., SMMPanel Pro" required>
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-error text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- URL -->
                    <div>
                        <label for="url" class="block text-sm font-medium text-on-surface mb-2">API URL</label>
                        <input type="url" id="url" name="url" value="<?php echo e(old('url')); ?>"
                               class="glass-input w-full" placeholder="https://api.example.com" required>
                        <?php $__errorArgs = ['url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-error text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- API Key -->
                    <div>
                        <label for="api_key" class="block text-sm font-medium text-on-surface mb-2">API Key</label>
                        <input type="password" id="api_key" name="api_key" value="<?php echo e(old('api_key')); ?>"
                               class="glass-input w-full" placeholder="Your API key" required>
                        <?php $__errorArgs = ['api_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-error text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Percentage Increase -->
                    <div>
                        <label for="percentage_increase" class="block text-sm font-medium text-on-surface mb-2">
                            Price Increase (%)
                        </label>
                        <input type="number" id="percentage_increase" name="percentage_increase"
                               value="<?php echo e(old('percentage_increase', 0)); ?>" step="0.01" min="0" max="10000"
                               class="glass-input w-full" placeholder="0.00" required>
                        <?php $__errorArgs = ['percentage_increase'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-error text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p class="text-on-surface-variant text-sm mt-1">
                            Percentage to increase prices above the provider's rates
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 mt-8">
                    <a href="<?php echo e(route('admin.providers.index')); ?>"
                       class="btn-ghost px-6 py-3 rounded-lg">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary px-6 py-3 rounded-lg">
                        Create Provider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\kbros\Desktop\production_panel\resources\views/admin/providers/create.blade.php ENDPATH**/ ?>