<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Cotización</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #007bff; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Nueva Solicitud de Cotización</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Se ha recibido una nueva solicitud de cotización con los siguientes datos:</p>
                            <p><strong>Nombre:</strong> {{ $cotizacion->nombre }}</p>
                            <p><strong>Correo:</strong> {{ $cotizacion->correo }}</p>
                            <p><strong>NIT:</strong> {{ $cotizacion->NIT }}</p>
                            <p><strong>Teléfono:</strong> {{ $cotizacion->telefono }}</p>
                            <p><strong>Descripción:</strong> {{ $cotizacion->descripcion }}</p>
                        </td>
                    </tr>
                    <!-- Imagen de referencia -->
                    <tr>
                        <td style="padding: 20px; text-align:center;">
                            <p style="margin-bottom:10px; font-weight:bold; color:#555;">Imagen de referencia adjunta:</p>
                            <img src="cid:imagenReferencia" alt="Imagen de referencia" style="max-width:100%; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <a href="{{ config('app.frontend_url') }}/Admin/Cotizaciones/{{ $cotizacion->id }}" style="display:inline-block; background-color:#007bff; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold;">
                                Ver lista de cotizaciones
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#777;">
                            Este correo es generado automáticamente por el sistema de cotizaciones.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

