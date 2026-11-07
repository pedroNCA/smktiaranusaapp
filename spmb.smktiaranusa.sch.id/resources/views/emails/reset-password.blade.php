<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pemulihan Password - SPMB SMK Tiara Nusa</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #f3f4f6;">
        
        <h2 style="color: #1f2937; text-align: center; margin-bottom: 20px;">Pemulihan Password Akun SPMB</h2>
        
        <p style="color: #4b5563; line-height: 1.6; font-size: 16px;">Halo,</p>
        <p style="color: #4b5563; line-height: 1.6; font-size: 16px;">Kami menerima permintaan untuk mengatur ulang password akun SPMB SMK Tiara Nusa Anda. Silakan klik tombol di bawah ini untuk membuat password baru:</p>

        <div style="text-align: center; margin: 35px 0;">
            <a href="{{ route('password.reset', ['token' => $token, 'email' => $email]) }}" style="background-color: #2563eb; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px;">Atur Ulang Password</a>
        </div>

        <p style="color: #6b7280; line-height: 1.6; font-size: 14px;">Tautan ini hanya berlaku untuk <strong>satu kali pemakaian</strong>. Jika Anda tidak merasa membuat permintaan ini, abaikan saja email ini dan akun Anda akan tetap aman.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #9ca3af; font-size: 13px; text-align: center; margin: 0;">
            Terima kasih,<br>
            <strong>Panitia SPMB SMK Tiara Nusa</strong>
        </p>
    </div>
</body>
</html>