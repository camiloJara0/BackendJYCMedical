<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Respuesta a Solicitud de Cotización</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #28a745; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Respuesta a su Solicitud de Cotización</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Estimado/a <strong>{{ $cotizacion->nombre }}</strong>,</p>
                            <p>Gracias por confiar en nosotros. A continuación encontrará la respuesta a su solicitud de cotización:</p>
                            <p><strong>Nombre:</strong> {{ $cotizacion->nombre }}</p>
                            <p><strong>Correo:</strong> {{ $cotizacion->correo }}</p>
                            <p><strong>NIT o Cédula:</strong> {{ $cotizacion->NIT }}</p>
                            <p><strong>Descripción:</strong> {{ $cotizacion->descripcion }}</p>
                            <hr style="border:none; border-top:1px solid #eee; margin:20px 0;">
                            <p><strong>Monto:</strong> {{ $respuesta->monto }}</p>
                            <p><strong>Observaciones:</strong> {{ $respuesta->observaciones_admin }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; text-align:center;">
                            <p style="margin-bottom:10px; font-weight:bold; color:#555;">Adjunto encontrará el archivo con el detalle completo de la cotización:</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#777;">
                            Este correo es generado automáticamente por el sistema Ananké.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
