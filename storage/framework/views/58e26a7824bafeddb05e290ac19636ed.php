<?php if (isset($component)) { $__componentOriginal798d48e855791091e969662b51ff7713 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal798d48e855791091e969662b51ff7713 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'marketers::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('marketers::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('marketers.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal798d48e855791091e969662b51ff7713)): ?>
<?php $attributes = $__attributesOriginal798d48e855791091e969662b51ff7713; ?>
<?php unset($__attributesOriginal798d48e855791091e969662b51ff7713); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal798d48e855791091e969662b51ff7713)): ?>
<?php $component = $__componentOriginal798d48e855791091e969662b51ff7713; ?>
<?php unset($__componentOriginal798d48e855791091e969662b51ff7713); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Marketers\resources\views\index.blade.php ENDPATH**/ ?>