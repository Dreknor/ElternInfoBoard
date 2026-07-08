<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="refresh" content="300">

    <link rel="shortcut icon" href="{{asset('img/'.config('app.favicon'))}}" type="image/x-icon">
    @stack('header')
    <title>{{config('app.name')}}</title>

    <!-- Alpine.js x-cloak -->
    <style>[x-cloak] { display: none !important; }</style>

    <!-- FontAwesome -->
    <link href="{{asset('/css/all.css')}}?v=1" rel="stylesheet">
    <script src="https://kit.fontawesome.com/c8f58e3eb6.js"></script>

    <!-- Palette-Gradient (vor Vite, da keine Konflikte) -->
    <link href="{{asset('css/palette-gradient.css')}}?v=1" rel="stylesheet" />

    <!-- Vite Assets (Tailwind CSS + Bootstrap-Compat-Layer) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Anwesenheits-spezifische Styles NACH Vite, damit sie Vorrang haben -->
    <link href="{{asset('css/anwesenheit.css')}}" rel="stylesheet" />

    @stack('head')
</head>
<body id="app-layout">
<div class="content">
    @if(session('Meldung'))
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-{{session('type')}} alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        {{session('Meldung')}}
                    </div>
                </div>
            </div>
        </div>
    @endif
    @yield('content')
</div>

<!-- jQuery -->
<script src="{{asset('js/core/jquery.min.js')}}"></script>

<!-- jQuery Modal/Tab-Shim (ersetzt Bootstrap JS) -->
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ !== 'undefined') {
            $.fn.modal = function (action) {
                var id = this.attr('id') || this.data('target')?.replace('#', '');
                if (!id) return this;
                var el = document.getElementById(id);
                if (!el) return this;

                if (action === 'show' || action === undefined) {
                    el.classList.add('show');
                    el.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    if (!document.querySelector('.modal-backdrop')) {
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop show';
                        backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:40;';
                        backdrop.onclick = function () { $('#' + id).modal('hide'); };
                        document.body.appendChild(backdrop);
                    }
                } else if (action === 'hide') {
                    el.classList.remove('show');
                    el.style.display = 'none';
                    var bd = document.querySelector('.modal-backdrop');
                    if (bd) bd.remove();
                    document.body.style.overflow = '';
                } else if (action === 'toggle') {
                    el.style.display === 'none' ? $(this).modal('show') : $(this).modal('hide');
                }
                return this;
            };

            $(document).on('click', '[data-dismiss="modal"]', function () {
                $(this).closest('.modal').modal('hide');
            });

            $(document).on('click', '[data-toggle="modal"]', function (e) {
                e.preventDefault();
                var target = $(this).data('target') || $(this).attr('href');
                $(target).modal('show');
            });

            $.fn.popover = function () { return this; };

            $.fn.tab = function (action) {
                if (action === 'show') {
                    var targetSelector = $(this).data('target') || $(this).attr('href');
                    if (!targetSelector) return this;
                    var parent = $(this).closest('[id]').parent();
                    parent.find('.nav-link').removeClass('active');
                    parent.find('.tab-pane').removeClass('active show');
                    $(this).addClass('active');
                    $(targetSelector).addClass('active show');
                }
                return this;
            };

            $(document).on('click', '[data-toggle="tab"]', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });

            $(document).on('click', '.nav-pills .nav-link', function (e) {
                var target = $(this).data('target') || $(this).attr('href');
                if (target && target.startsWith('#')) {
                    e.preventDefault();
                    $(this).closest('.nav-pills').find('.nav-link').removeClass('active');
                    $(this).addClass('active');
                    var tabContent = $(this).closest('.card-body, .p-4').find('.tab-content');
                    if (!tabContent.length) tabContent = $(this).closest('.modal-content').find('.tab-content');
                    if (!tabContent.length) tabContent = $(target).parent();
                    tabContent.find('.tab-pane').removeClass('active show');
                    $(target).addClass('active show');
                }
            });
        }
    });
})();
</script>

@stack('js')
</body>
</html>
