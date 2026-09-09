<?php if (isset($component)) { $__componentOriginalfd2880afbd8da4c405e8e019076b9a16 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfd2880afbd8da4c405e8e019076b9a16 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admins::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admins::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('admins.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfd2880afbd8da4c405e8e019076b9a16)): ?>
<?php $attributes = $__attributesOriginalfd2880afbd8da4c405e8e019076b9a16; ?>
<?php unset($__attributesOriginalfd2880afbd8da4c405e8e019076b9a16); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfd2880afbd8da4c405e8e019076b9a16)): ?>
<?php $component = $__componentOriginalfd2880afbd8da4c405e8e019076b9a16; ?>
<?php unset($__componentOriginalfd2880afbd8da4c405e8e019076b9a16); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Admins\resources\views\index.blade.php ENDPATH**/ ?>