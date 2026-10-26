<!-- latest jquery-->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!-- Bootstrap js-->
<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
<!-- feather icon js-->
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
<!-- scrollbar js-->
<script src="{{ asset('assets/js/scrollbar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/scrollbar/custom.js') }}"></script>
<!-- Sidebar jquery-->
<script src="{{ asset('assets/js/config.js') }}"></script>
<!-- Plugins JS start-->
<script src="{{ asset('assets/js/sidebar-menu.js') }}"></script>
<script src="{{ asset('assets/js/sidebar-pin.js') }}"></script>
@yield('scripts')
<!-- Plugins JS Ends-->
<script src="{{ asset('assets/js/script.js') }}"></script>
{{--
    Cuba's theme customizer is deliberately NOT loaded.

    assets/js/theme-customizer/customizer.js appends a floating
    `.sidebar-panel-main` to <body> — the "Layouts" and "Settings" buttons that
    sat in the bottom-right corner of every admin page, opening the template's
    own preview panels (layout pickers, colour swatches, LTR/RTL, dark mode,
    plus Pixelstrap's Support / Document / Buy Now links). It is a demo control
    for the theme, not a feature of this panel, and it let anyone reskin the
    admin from the browser.

    Dropping the script is the whole fix, and it costs nothing. Besides drawing
    that panel the script only ever did two things: re-apply a colour or dark
    choice saved in localStorage, and call setLayout("dubai") — which sets
    `page-wrapper compact-wrapper`, exactly what layouts/simple/master.blade.php
    already hardcodes, while css.blade.php already links color-1.css. So the
    panel renders identically with it gone.
--}}
<script src="{{ asset('assets/js/toastr.min.js') }}"></script>
@include('admin.inc.status_toggle')
