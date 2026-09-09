<?php if (isset($component)) { $__componentOriginalb8a0342782f0492952f6072d9513d7ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb8a0342782f0492952f6072d9513d7ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'cart::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('cart::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('cart.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb8a0342782f0492952f6072d9513d7ad)): ?>
<?php $attributes = $__attributesOriginalb8a0342782f0492952f6072d9513d7ad; ?>
<?php unset($__attributesOriginalb8a0342782f0492952f6072d9513d7ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb8a0342782f0492952f6072d9513d7ad)): ?>
<?php $component = $__componentOriginalb8a0342782f0492952f6072d9513d7ad; ?>
<?php unset($__componentOriginalb8a0342782f0492952f6072d9513d7ad); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Cart\resources\views\index.blade.php ENDPATH**/ ?>