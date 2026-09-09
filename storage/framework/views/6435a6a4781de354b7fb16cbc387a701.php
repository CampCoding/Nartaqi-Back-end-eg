<?php if (isset($component)) { $__componentOriginala98b6121b0225c9cf944740042a7fc64 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala98b6121b0225c9cf944740042a7fc64 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'home::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('home::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('home.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala98b6121b0225c9cf944740042a7fc64)): ?>
<?php $attributes = $__attributesOriginala98b6121b0225c9cf944740042a7fc64; ?>
<?php unset($__attributesOriginala98b6121b0225c9cf944740042a7fc64); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala98b6121b0225c9cf944740042a7fc64)): ?>
<?php $component = $__componentOriginala98b6121b0225c9cf944740042a7fc64; ?>
<?php unset($__componentOriginala98b6121b0225c9cf944740042a7fc64); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Home\resources\views\index.blade.php ENDPATH**/ ?>