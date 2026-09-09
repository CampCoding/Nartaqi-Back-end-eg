<?php if (isset($component)) { $__componentOriginaleae3381f0dacdb5f9328cde9c707a9b5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleae3381f0dacdb5f9328cde9c707a9b5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'badges::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badges::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('badges.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleae3381f0dacdb5f9328cde9c707a9b5)): ?>
<?php $attributes = $__attributesOriginaleae3381f0dacdb5f9328cde9c707a9b5; ?>
<?php unset($__attributesOriginaleae3381f0dacdb5f9328cde9c707a9b5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleae3381f0dacdb5f9328cde9c707a9b5)): ?>
<?php $component = $__componentOriginaleae3381f0dacdb5f9328cde9c707a9b5; ?>
<?php unset($__componentOriginaleae3381f0dacdb5f9328cde9c707a9b5); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Badges\resources\views\index.blade.php ENDPATH**/ ?>