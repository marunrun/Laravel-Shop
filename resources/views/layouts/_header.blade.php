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
                <li class="nav-item"><a href="#" class="nav-link">登陆</a></li>
                <li class="nav-item"><a href="#" class="nav-link">注册</a></li>
            </ul>
        </div>

    </div>
</nav>