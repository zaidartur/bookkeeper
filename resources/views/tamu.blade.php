<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Data Center Diskominfo Karanganyar">
        <meta name="generator" content="Hugo 0.84.0">
        <title>Import Data Buku Tamu</title>

        <link rel="icon" type="image/png" href="{{ asset('icon.ico') }}">
        <!-- Bootstrap core CSS -->
        <link href="{{ asset('assets/bootstrap5/dist/css/bootstrap.min.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <style>
            .bd-placeholder-img {
                font-size: 1.125rem;
                text-anchor: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                user-select: none;
            }

            @media (min-width: 768px) {
                .bd-placeholder-img-lg {
                font-size: 3.5rem;
                }
            }
        </style>

        
        <!-- Custom styles for this template -->
        <style>
                html, body {
                    height: 100%;
                }

                body {
                    display: flex;
                    align-items: center;
                    padding-top: 40px;
                    padding-bottom: 40px;
                    background-color: #f5f5f5;
                }

                .form-signin {
                    width: 100%;
                    max-width: 330px;
                    padding: 15px;
                    margin: auto;
                }

                .form-signin .checkbox {
                    font-weight: 400;
                }

                .form-signin .form-floating:focus-within {
                    z-index: 2;
                }

                .form-signin input[type="email"] {
                    margin-bottom: -1px;
                    border-bottom-right-radius: 0;
                    border-bottom-left-radius: 0;
                }

                .form-signin input[type="password"] {
                    margin-bottom: 10px;
                    border-top-left-radius: 0;
                    border-top-right-radius: 0;
                }
        </style>
    </head>
    <body class="text-center">
    
        <main class="form-signin">
            <form action="{{ route('import.save') }}" method="POST" id="formImport" enctype="multipart/form-data">
                @csrf
                <img class="mb-4" src="{{ asset('assets/images/icon_logo.png') }}" alt="" width="72" height="57">
                <h1 class="h3 mb-3 fw-normal">Import Data Buku Tamu</h1>

                <div class="mb-3">
                    <label for="file_import" class="form-label mb-3">Masukkan file excel (.xlsx, max 5Mb)</label>
                    <input type="file" id="file_import" name="file_import" accept=".xlsx" placeholder="Masukkan file excel">
                </div>
                
                <button class="w-100 btn btn-lg btn-primary mb-3" type="submit"><i class="fa fa-save"></i> Konfirmasi</button>
                <button class="w-100 btn btn-lg btn-info" type="button" onclick="location.href='/dashboard'"><i class="fa fa-home"></i> Beranda</button>
            </form>
        </main>
    
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            })


            $(document).ready(function() {
                @if (Session::has('res') && Session::get('res') == 'success')
                    // console.log('success')
                    Swal.fire({
                        title: 'Sukses',
                        text: `{{ Session::get('success') }} berhasil di impor dari total {{ Session::get('total') }} data`,
                        icon: "success"
                    })
                @endif

                @if (Session::has('res') && Session::get('res') == 'failed')
                    // console.log('error')
                    Toast.fire({
                        icon: 'error',
                        title: 'File gagal di impor!'
                    })
                @endif

                const myForm = document.getElementById('formImport');
                myForm.addEventListener('submit', function(e) {
                    let files = $('#file_import')
                    const options = files[0].files[0]

                    if (files.val() && options.name.split('.').pop() === 'xlsx' && options.size <= (5 * 1024 * 1024)) {
                        $('#formImport').submit()
                    } else {
                        e.preventDefault()
                        Toast.fire({
                            icon: 'error',
                            title: 'Jenis file harus Excel (.xlsx) dan ukuran file maksimal 5Mb.'
                        })
                    }
                })
            })
        </script>
    </body>
</html>