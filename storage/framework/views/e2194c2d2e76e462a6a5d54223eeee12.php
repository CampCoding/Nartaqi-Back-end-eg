<?php if (isset($component)) { $__componentOriginald6cca8fadfff8aa014152639f500673b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald6cca8fadfff8aa014152639f500673b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'team::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('team::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('team.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald6cca8fadfff8aa014152639f500673b)): ?>
<?php $attributes = $__attributesOriginald6cca8fadfff8aa014152639f500673b; ?>
<?php unset($__attributesOriginald6cca8fadfff8aa014152639f500673b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald6cca8fadfff8aa014152639f500673b)): ?>
<?php $component = $__componentOriginald6cca8fadfff8aa014152639f500673b; ?>
<?php unset($__componentOriginald6cca8fadfff8aa014152639f500673b); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Team\resources\views\index.blade.php ENDPATH**/ ?>