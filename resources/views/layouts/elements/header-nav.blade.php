<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent ">
    <div class="container-fluid">
        <div class="row w-100">
            <div class="col-auto">
                <div class="navbar-wrapper">
                    <div class="navbar-toggle">
                        <button type="button" class="navbar-toggler">
                            <span class="navbar-toggler-bar bar1"></span>
                            <span class="navbar-toggler-bar bar2"></span>
                            <span class="navbar-toggler-bar bar3"></span>
                        </button>
                    </div>
                    <span class="d-md-none">
                <a class="navbar-brand" href="{{url('/')}}">
                    <i class="fas fa-home" style="font-size: 1.5rem"></i>
                </a>
            </span>
                    <span class="d-none d-md-block">
                <a class="navbar-brand" href="{{url('/')}}">{{config('app.name')}}</a>
            </span>

                </div>
            </div>
            <div class="col-auto my-auto ml-auto ">

            <div class="nav-item">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-search" style="font-size: 1.4rem"></i>
                    </a>
                    <ul class="dropdown-menu nav-dropdown-content dropdown-menu-right">
                        <li class="dropdown-header">
                            <b>
                                Suche
                            </b>
                        </li>
                        <li class="dropdown-item" style="min-width: 250px;">
                            <form class="form-inline " role="search" method="post" action="{{url('search')}}"
                                  id="searchForm">
                                @csrf
                                <div class="form-group">
                                    <label for="suchInput" class="sr-only">Suche</label>
                                    <input type="text" class="form-control my-auto" placeholder="Suchen" name="suche"
                                           id="suchInput" style="min-width: 200px;">

                                </div>
                                <button type="submit" class="btn btn-block" id="searchButton">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </li>

                    </ul>
                </div>
            </div>

            <div class="col-auto  my-auto  ">
                @include('include.benachrichtigung')
            </div>
            <div class="col-auto my-auto  ">
                <div class="nav-item">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <i class="far fa-user" style="font-size: 1.5rem"></i>
                    </a>
                    <ul class="dropdown-menu nav-dropdown-content dropdown-menu-right">
                        <li class="dropdown-header">
                            <b>
                                {{auth()->user()->name}}
                            </b>
                        @stack('nav-user')

                        <li>
                            <a class="dropdown-item" href="#"
                               onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

