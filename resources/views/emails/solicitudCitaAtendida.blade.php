<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Respuesta a su Solicitud de Cita</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #28a745; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Respuesta a su Solicitud de Cita</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Estimado/a <strong>{{ $solicitud->nombre_contacto }}</strong>,</p>
                            <p>Hemos revisado su solicitud de cita de <strong>{{ ucfirst($solicitud->tipo_cita) }}</strong> y a continuación encontrará nuestra respuesta:</p>

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Datos de su Solicitud</h3>
                            <p><strong>Tipo de Cita:</strong> {{ ucfirst($solicitud->tipo_cita) }}</p>
                            <p><strong>Motivo:</strong> {{ $solicitud->motivo ?: 'No especificado' }}</p>
                            @if($solicitud->serial_equipo)
                                <p><strong>Equipo:</strong> {{ $solicitud->marca }} {{ $solicitud->modelo }} (Serie: {{ $solicitud->serial_equipo }})</p>
                            @endif

                            <hr style="border:none; border-top:1px solid #eee; margin:20px 0;">

                            <h3 style="color:#28a745; border-bottom:2px solid #28a745; padding-bottom:5px;">Respuesta del Administrador</h3>
                            <div style="background-color:#f8f9fa; padding:15px; border-radius:6px; border-left:4px solid #28a745;">
                                <p style="margin:0; white-space:pre-wrap;">{{ $respuesta }}</p>
                            </div>

                            @if($solicitud->archivo_respuesta)
                                <div style="margin-top:20px; text-align:center;">
                                    <p style="font-weight:bold; color:#555;">Se ha adjuntado un archivo a esta respuesta.</p>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#777;">
                            Este correo es generado automáticamente por el sistema de citas de J&C Medical.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
