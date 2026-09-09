<?php if (isset($component)) { $__componentOriginal4e79639de9e8b0aa06e20ad3295a7763 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4e79639de9e8b0aa06e20ad3295a7763 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'certificates::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('certificates::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('certificates.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4e79639de9e8b0aa06e20ad3295a7763)): ?>
<?php $attributes = $__attributesOriginal4e79639de9e8b0aa06e20ad3295a7763; ?>
<?php unset($__attributesOriginal4e79639de9e8b0aa06e20ad3295a7763); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4e79639de9e8b0aa06e20ad3295a7763)): ?>
<?php $component = $__componentOriginal4e79639de9e8b0aa06e20ad3295a7763; ?>
<?php unset($__componentOriginal4e79639de9e8b0aa06e20ad3295a7763); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Certificates\resources\views\index.blade.php ENDPATH**/ ?>