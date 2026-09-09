<?php if (isset($component)) { $__componentOriginal69e438cbfc5ebc2b5509a8c39fad1f29 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69e438cbfc5ebc2b5509a8c39fad1f29 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'blogs::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('blogs::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('blogs.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69e438cbfc5ebc2b5509a8c39fad1f29)): ?>
<?php $attributes = $__attributesOriginal69e438cbfc5ebc2b5509a8c39fad1f29; ?>
<?php unset($__attributesOriginal69e438cbfc5ebc2b5509a8c39fad1f29); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69e438cbfc5ebc2b5509a8c39fad1f29)): ?>
<?php $component = $__componentOriginal69e438cbfc5ebc2b5509a8c39fad1f29; ?>
<?php unset($__componentOriginal69e438cbfc5ebc2b5509a8c39fad1f29); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Blogs\resources\views\index.blade.php ENDPATH**/ ?>