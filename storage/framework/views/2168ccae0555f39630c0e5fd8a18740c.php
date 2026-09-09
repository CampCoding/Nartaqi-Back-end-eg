<?php if (isset($component)) { $__componentOriginalde83d9835ece4a41648368bc73239c75 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalde83d9835ece4a41648368bc73239c75 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'faqs::components.layouts.master','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('faqs::layouts.master'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <h1>Hello World</h1>

    <p>Module: <?php echo config('faqs.name'); ?></p>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalde83d9835ece4a41648368bc73239c75)): ?>
<?php $attributes = $__attributesOriginalde83d9835ece4a41648368bc73239c75; ?>
<?php unset($__attributesOriginalde83d9835ece4a41648368bc73239c75); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalde83d9835ece4a41648368bc73239c75)): ?>
<?php $component = $__componentOriginalde83d9835ece4a41648368bc73239c75; ?>
<?php unset($__componentOriginalde83d9835ece4a41648368bc73239c75); ?>
<?php endif; ?>
<?php /**PATH D:\campCoding\Nartaqi-Back-end\Modules\Faqs\resources\views\index.blade.php ENDPATH**/ ?>