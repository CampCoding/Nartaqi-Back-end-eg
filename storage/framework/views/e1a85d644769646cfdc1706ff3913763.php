<?php if (isset($component)) { $__componentOriginale67e65e656bc4c775db8727350112d70 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale67e65e656bc4c775db8727350112d70 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'store::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('store::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('store.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale67e65e656bc4c775db8727350112d70)): ?>
<?php $attributes = $__attributesOriginale67e65e656bc4c775db8727350112d70; ?>
<?php unset($__attributesOriginale67e65e656bc4c775db8727350112d70); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale67e65e656bc4c775db8727350112d70)): ?>
<?php $component = $__componentOriginale67e65e656bc4c775db8727350112d70; ?>
<?php unset($__componentOriginale67e65e656bc4c775db8727350112d70); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Store\resources\views\index.blade.php ENDPATH**/ ?>