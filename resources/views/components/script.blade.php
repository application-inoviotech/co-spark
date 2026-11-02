@props(['src'])
<script src="{{ asset('assets/js/'.$src) }}?v={{ \App\Support\AssetVersion::for('assets/js/'.$src) }}"></script>
