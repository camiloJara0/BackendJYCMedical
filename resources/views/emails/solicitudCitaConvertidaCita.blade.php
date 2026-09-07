<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Su Solicitud fue Agendada</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #2262a3; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Su Solicitud fue Agendada</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Estimado/a <strong>{{ $solicitud->nombre_contacto }}</strong>,</p>
                            <p>Le informamos que su solicitud de cita ha sido agendada exitosamente. A continuación los detalles:</p>

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Detalles de la Cita</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="background-color:#f8f9fa; border-radius:6px;">
                                <tr>
                                    <td style="border-bottom:1px solid #eee;"><strong>Fecha:</strong></td>
                                    <td style="border-bottom:1px solid #eee;">{{ \Carbon\Carbon::parse($cita->fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #eee;"><strong>Hora:</strong></td>
                                    <td style="border-bottom:1px solid #eee;">{{ $cita->hora }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #eee;"><strong>Tipo de Servicio:</strong></td>
                                    <td style="border-bottom:1px solid #eee;">{{ ucfirst($cita->tipo) }}</td>
                                </tr>
                                @if($cita->tecnico)
                                <tr>
                                    <td style="border-bottom:1px solid #eee;"><strong>Técnico Asignado:</strong></td>
                                    <td style="border-bottom:1px solid #eee;">{{ $cita->tecnico->nombre }}</td>
                                </tr>
                                @endif
                                @if($cita->equipo)
                                <tr>
                                    <td><strong>Equipo:</strong></td>
                                    <td>{{ $cita->equipo->marca }} {{ $cita->equipo->modelo }} (Serie: {{ $cita->equipo->serie }})</td>
                                </tr>
                                @endif
                            </table>

                            <hr style="border:none; border-top:1px solid #eee; margin:20px 0;">

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Datos de su Solicitud Original</h3>
                            <p><strong>Tipo de Cita Solicitada:</strong> {{ ucfirst($solicitud->tipo_cita) }}</p>
                            <p><strong>Motivo:</strong> {{ $solicitud->motivo ?: 'No especificado' }}</p>
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
