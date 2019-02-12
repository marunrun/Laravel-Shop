<nav class="navbar navbar-expand-lg navbar-light bg-light navbar-static-top">
    <div class="container">
        {{--品牌名称--}}
        <a href="{{ url('/') }}" class="navbar-brand">
            Laravel Shop
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            {{--左侧的导航--}}
            <ul class="navbar-nav mr-auto">

            </ul>

            {{--右侧导航--}}
            <ul class="navbar-nav navbar-right">
                {{--登陆注册链接--}}
                @guest
                    <li class="nav-item"><a href="{{ route('login') }}" class="nav-link">登陆</a></li>
                    <li class="nav-item"><a href="{{ route('register') }}" class="nav-link">注册</a></li>
                @else
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="https://iocaffcdn.phphub.org/uploads/images/201709/20/1/PtDKbASVcz.png?imageView2/1/w/60/h/60"
                                 class="img-responsive img-circle" width="30px" height="30px">
                            {{ Auth::user()->name }}
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a href="{{ route('user_addresses.index') }}" class="dropdown-item">收获地址</a>
                            <a href="#" class="dropdown-item" id="logout"
                               onclick="event.preventDefault();document.getElementById('logout-form').submit();">退出登陆</a>
                            <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">
                                {{csrf_field()}}
                            </form>
                        </div>
                    </li>
                @endguest
                {{--登陆注册链接结束--}}
            </ul>
        </div>
    </div>
</nav>