@component('mail::message')
# Verifikasi Akun SPMB SMK Tiara Nusa

Halo,

Terima kasih telah mendaftar di portal Penerimaan Peserta Didik Baru (SPMB) SMK Tiara Nusa. Untuk melanjutkan proses pendaftaran, silakan gunakan kode OTP di bawah ini:

@component('mail::panel')
# <center>{{ $otp }}</center>
@endcomponent

Kode OTP ini bersifat rahasia dan **hanya berlaku selama 5 menit** ke depan. Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengatasnamakan sekolah.

Jika Anda tidak merasa melakukan pendaftaran ini, abaikan saja email ini.

Hormat kami,<br>
**Panitia SPMB SMK Tiara Nusa**
@endcomponent