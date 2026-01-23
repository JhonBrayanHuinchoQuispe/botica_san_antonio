<script src="{{ asset('assets/js/lib/jquery-3.7.1.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/apexcharts.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/simple-datatables.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/iconify-icon.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/jquery-ui.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}"></script>
    <script src="{{ asset('assets/js/lib/jquery-jvectormap-world-mill-en.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/magnifc-popup.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/slick.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/prism.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/file-upload.js') }}"></script>
    
    <script src="{{ asset('assets/js/lib/audioplayer.js') }}"></script>

    <script src="{{ asset('assets/js/flowbite.min.js') }}"></script>
    
    <script src="{{ asset('assets/js/app.js') }}"></script>
    
    <script src="{{ asset('assets/js/navbar-search.js') }}"></script>
    
    <script src="{{ asset('assets/js/pwa-install.js') }}"></script>

    <?php echo (isset($script) ? $script   : '')?>

    @stack('scripts')