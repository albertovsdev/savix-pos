<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $pedido->folio_ticket }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 0; padding: 20px; }
        main { width: {{ $negocio->ancho_ticket_mm ?? 80 }}mm; max-width: calc(100vw - 40px); margin: 0 auto; } h1, p { margin: 0 0 8px; }
        .centrado { text-align: center; } .logo { display: block; max-width: 120px; max-height: 72px; margin: 0 auto 10px; object-fit: contain; } .separador { border: 0; border-top: 1px dashed #555; margin: 14px 0; }
        .fila { display: flex; justify-content: space-between; gap: 12px; margin: 5px 0; }
        .muted { color: #444; font-size: 12px; } .total { font-size: 18px; font-weight: 700; }
        button { display: block; width: 100%; margin: 16px 0; padding: 10px; }
        @media print { @page { size: {{ $negocio->ancho_ticket_mm ?? 80 }}mm auto; margin: 0; } body { padding: 0; } button { display: none; } }
    </style>
</head>
<body>
    <main>
        <button type="button" onclick="window.print()">Imprimir ticket</button>
        <header class="centrado">
            @if($negocio->logo_url)
                <img class="logo" src="{{ $negocio->logo_url }}" alt="Logo de {{ $negocio->nombre_comercial }}">
            @endif
            <h1>{{ $negocio->nombre_comercial }}</h1>
            @if($negocio->nombre && $negocio->nombre !== $negocio->nombre_comercial)<p class="muted">{{ $negocio->nombre }}</p>@endif
            @if($negocio->rfc)<p class="muted">RFC: {{ $negocio->rfc }}</p>@endif
            @if($negocio->telefono)<p class="muted">Tel: {{ $negocio->telefono }}</p>@endif
            @if($negocio->direccion_ticket)<p class="muted">{!! nl2br(e($negocio->direccion_ticket)) !!}</p>@endif
            <p class="muted">Folio: {{ $pedido->folio_ticket }}</p>
            <p class="muted">{{ $cuenta['referencia'] }} · {{ optional($pedido->cerrado_en)->format('d/m/Y H:i') }}</p>
        </header>
        <hr class="separador">
        @foreach($cuenta['partes'] as $parte)
            <section>
                <strong>{{ $parte['nombre'] }}</strong>
                @foreach($detalles_por_parte->get($parte['id_parte_cuenta_pedido'], collect()) as $detalle)
                    <div class="fila"><span>{{ $detalle->cantidad }} × {{ $detalle->nombre_producto }}</span><span>${{ number_format($detalle->importe_total, 2) }}</span></div>
                @endforeach
                @foreach($pagos_por_parte->get($parte['id_parte_cuenta_pedido'], collect()) as $pago)
                    <div class="fila muted"><span>{{ ucfirst($pago->tipo_pago) }}</span><span>${{ number_format($pago->importe, 2) }}</span></div>
                @endforeach
                <div class="fila"><strong>Subtotal</strong><strong>${{ number_format($parte['importe_total'], 2) }}</strong></div>
                <hr class="separador">
            </section>
        @endforeach
        <div class="fila total"><span>Total</span><span>${{ number_format($cuenta['importe_total'], 2) }}</span></div>
        @if($negocio->pie_ticket)<p class="centrado muted">{!! nl2br(e($negocio->pie_ticket)) !!}</p>@endif
    </main>
</body>
</html>
