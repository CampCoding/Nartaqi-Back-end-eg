<?php if (isset($component)) { $__componentOriginale798258bd682bc1d51f19d91f64a7ce8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale798258bd682bc1d51f19d91f64a7ce8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'authentication::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('authentication::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('authentication.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale798258bd682bc1d51f19d91f64a7ce8)): ?>
<?php $attributes = $__attributesOriginale798258bd682bc1d51f19d91f64a7ce8; ?>
<?php unset($__attributesOriginale798258bd682bc1d51f19d91f64a7ce8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale798258bd682bc1d51f19d91f64a7ce8)): ?>
<?php $component = $__componentOriginale798258bd682bc1d51f19d91f64a7ce8; ?>
<?php unset($__componentOriginale798258bd682bc1d51f19d91f64a7ce8); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Authentication\resources\views\index.blade.php ENDPATH**/ ?>