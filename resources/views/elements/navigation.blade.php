<div class="navbar-header">

    <!-- Collapsed Hamburger -->
    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
        <span class="sr-only">Toggle Navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>

    <!-- Branding Image -->
    <a class="navbar-brand" href="{{ url('/') }}">
        Klamottenbörsen-Verwaltung
    </a>

</div>

<div class="collapse navbar-collapse" id="app-navbar-collapse">
    <!-- Left Side Of Navbar -->




    <!-- Right Side Of Navbar -->

        <!-- Authentication Links -->
        @if (Auth::guest())
            <ul class="nav navbar-nav navbar-right">
                <li><a href="{{ url('/login') }}">Login</a></li>
            </ul>
        @else
             <ul class="nav navbar-nav">
                <li class="dropdown">

                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        
                        Interessenten
                        <span class="caret"></span>
                    </a>

                    <ul class="dropdown-menu" role="menu">
                        <li>
                            <a href="{{ url('/Ueberblick') }}">
                                Übersicht
                            </a>
                            <a href="{{ url('/Anlegen') }}">
                                Anlegen
                            </a>
                        </li>
                    </ul>
                </li>

                 <li class="dropdown">

                     <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                         <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
                         Klamottenbörse
                         <span class="caret"></span>
                     </a>

                     <ul class="dropdown-menu" role="menu">
                         <li>
                             <a href="{{ url('/Grunddaten') }}">
                                 Grunddaten
                             </a>
                         </li>
                         <li>
                             <a href="{{ url('/Nummern') }}">
                                 Verkäufernummern
                             </a>
                         </li>

                         <li>
                             <a href="{{ url('/Dateien') }}">
                                 Dateien
                             </a>
                         </li>
                         <li>
                             <a href="{{ url('/Listen') }}">
                                 Listen erstellen
                             </a>
                         </li>
                         <li>
                             <a href="{{ url('/Mailvorlagen') }}">
                                 Mailvorlagen bearbeiten
                             </a>
                         </li>
                     </ul>
                 </li>

            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                        {{ Auth::user()->name }}  <span class="caret"></span>
                    </a>

                    <ul class="dropdown-menu" role="menu">
                        <li><a href="{{ url('/logout') }}"><i class="fa fa-btn fa-sign-out"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        @endif

</div>