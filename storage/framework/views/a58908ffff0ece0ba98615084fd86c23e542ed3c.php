<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     
    <meta name="csrf-token" content="<?php echo e(csrf_token(), false); ?>">
    <title><?php echo $__env->yieldContent('title','Laravel Shop'); ?> - Laravel 电商 </title>
    
    <link rel="stylesheet" href="<?php echo e(mix('css/app.css'), false); ?>">
</head>
<body>
    <div id="app" class="<?php echo e(route_class(), false); ?>-page">
        <?php echo $__env->make('layouts._header', \Illuminate\Support\Arr::except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        <div class="container">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
        <?php echo $__env->make('layouts._footer', \Illuminate\Support\Arr::except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    </div>

    
    <script src="<?php echo e(mix('js/app.js'), false); ?>"></script>
    <?php echo $__env->yieldContent('scriptsAfterJs'); ?>
</body>
</html>