<?php if (isset($component)) { $__componentOriginal33e5ac25f01e0316a89e828dad2bc119 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33e5ac25f01e0316a89e828dad2bc119 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'courses::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('courses::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('courses.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33e5ac25f01e0316a89e828dad2bc119)): ?>
<?php $attributes = $__attributesOriginal33e5ac25f01e0316a89e828dad2bc119; ?>
<?php unset($__attributesOriginal33e5ac25f01e0316a89e828dad2bc119); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33e5ac25f01e0316a89e828dad2bc119)): ?>
<?php $component = $__componentOriginal33e5ac25f01e0316a89e828dad2bc119; ?>
<?php unset($__componentOriginal33e5ac25f01e0316a89e828dad2bc119); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Courses\resources\views\index.blade.php ENDPATH**/ ?>