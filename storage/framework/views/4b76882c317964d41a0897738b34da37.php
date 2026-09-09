<?php if (isset($component)) { $__componentOriginale14000338d461ac70835bc6bf528fd33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale14000338d461ac70835bc6bf528fd33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'favourite::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('favourite::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('favourite.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale14000338d461ac70835bc6bf528fd33)): ?>
<?php $attributes = $__attributesOriginale14000338d461ac70835bc6bf528fd33; ?>
<?php unset($__attributesOriginale14000338d461ac70835bc6bf528fd33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale14000338d461ac70835bc6bf528fd33)): ?>
<?php $component = $__componentOriginale14000338d461ac70835bc6bf528fd33; ?>
<?php unset($__componentOriginale14000338d461ac70835bc6bf528fd33); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Favourite\resources\views\index.blade.php ENDPATH**/ ?>