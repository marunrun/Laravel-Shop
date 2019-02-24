<nav class="navbar navbar-expand-lg navbar-light bg-light navbar-static-top">
    <div class="container">
        
        <a href="<?php echo e(url('/'), false); ?>" class="navbar-brand">
            Laravel Shop
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            
            <ul class="navbar-nav mr-auto">

            </ul>

            
            <ul class="navbar-nav navbar-right">
                
                <?php if(auth()->guard()->guest()): ?>
                    <li class="nav-item"><a href="<?php echo e(route('login'), false); ?>?from=<?php echo e(Request::path(), false); ?>" class="nav-link">登陆</a></li>
                    <li class="nav-item"><a href="<?php echo e(route('register'), false); ?>" class="nav-link">注册</a></li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('cart.index'), false); ?>" class="nav-link mt-1"><i class="fa fa-shopping-cart"></i></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="https://iocaffcdn.phphub.org/uploads/images/201709/20/1/PtDKbASVcz.png?imageView2/1/w/60/h/60"
                                 class="img-responsive img-circle" width="30px" height="30px">
                            <?php echo e(Auth::user()->name, false); ?>

                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a href="<?php echo e(route('user_addresses.index'), false); ?>" class="dropdown-item">收获地址</a>
                            <a href="<?php echo e(route('orders.index'), false); ?>" class="dropdown-item">我的订单</a>
                            <a href="<?php echo e(route('products.favorites'), false); ?>" class="dropdown-item">我的收藏</a>
                            <a href="#" class="dropdown-item" id="logout"
                               onclick="event.preventDefault();document.getElementById('logout-form').submit();">退出登陆</a>
                            <form action="<?php echo e(route('logout'), false); ?>" method="POST" id="logout-form" style="display: none;">
                                <?php echo e(csrf_field(), false); ?>

                            </form>
                        </div>
                    </li>
                <?php endif; ?>
                
            </ul>
        </div>
    </div>
</nav>