<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Restablecida</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>

        Swal.fire({
            icon: 'success',
            title: '¡Contraseña Restablecida!',
            text: '{{ $message }}',
            showConfirmButton: false,
            timer: 1400,
            timerProgressBar: true
        }).then(() => {
            window.location.href = '{{ route("login") }}';
        });
    </script>
</body>
</html>