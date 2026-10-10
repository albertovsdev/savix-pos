<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    sucursal_seleccionada: { type: Object, required: true }, sucursales: { type: Array, default: () => [] },
    pedidos_abiertos: { type: Array, default: () => [] }, pedido: { type: Object, default: null },
});

const mensaje = computed(() => usePage().props.flash?.exito);
const nueva_parte = useForm({ nombre: '', ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const cobro = useForm({ pagos: [{ tipo_pago: 'efectivo', importe: '', referencia: '' }], ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const parte_cobrando = ref(null);
const cambiarSucursal = (evento) => router.get('/caja', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const abrirPedido = (item) => router.get('/caja', { ref_sucursal: props.sucursal_seleccionada.id_sucursal, ref_pedido: item.id_pedido }, { preserveState: false, preserveScroll: true });
const crearParte = () => nueva_parte.post(`/caja/pedidos/${props.pedido.id_pedido}/partes`, { preserveScroll: true, onSuccess: () => nueva_parte.reset('nombre') });
const asignarDetalle = (detalle, idParte) => router.put(`/caja/detalles/${detalle.id_detalle_ronda_pedido}/parte`, { ref_sucursal: props.sucursal_seleccionada.id_sucursal, ref_parte_cuenta_pedido: idParte }, { preserveScroll: true });
const prepararCobro = (parte) => { parte_cobrando.value = parte; cobro.pagos = [{ tipo_pago: 'efectivo', importe: Number(parte.importe_total).toFixed(2), referencia: '' }]; };
const agregarPago = () => cobro.pagos.push({ tipo_pago: 'tarjeta', importe: '', referencia: '' });
const quitarPago = (indice) => { if (cobro.pagos.length > 1) cobro.pagos.splice(indice, 1); };
const totalPagos = computed(() => cobro.pagos.reduce((total, pago) => total + (Number(pago.importe) || 0), 0));
const diferenciaPagos = computed(() => Math.round(((totalPagos.value - Number(parte_cobrando.value?.importe_total || 0)) * 100)) / 100);
const cobroExacto = computed(() => parte_cobrando.value && Math.abs(diferenciaPagos.value) < 0.005);
const cobrar = () => cobro.post(`/caja/partes/${parte_cobrando.value.id_parte_cuenta_pedido}/cobrar`, { preserveScroll: true, onSuccess: () => { parte_cobrando.value = null; cobro.reset(); cobro.pagos = [{ tipo_pago: 'efectivo', importe: '', referencia: '' }]; } });
const referenciaPedido = (item) => item.tipo_servicio === 'mesa' ? item.mesa : item.referencia_mostrador;
</script>

<template>
    <Head title="Caja" />
    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca"><span class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">Caja</p><p class="savix-pos-marca__subtitulo">Cuentas, pagos y tickets</p></div></div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>

        <section class="savix-pos-administracion__contenido">
            <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
            <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">COBRO</p><h1>{{ pedido ? `${pedido.referencia} · cuenta abierta` : 'Selecciona una cuenta abierta' }}</h1><p v-if="pedido">Divide, asigna productos y cobra cada parte por separado.</p><p v-else>Las mesas se liberan al cobrar todas las partes de su cuenta.</p></div>
            <label class="savix-pos-campo savix-pos-campo--selector-sucursal"><span>Sucursal</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>

            <section v-if="!pedido" class="savix-pos-seccion"><div class="savix-pos-seccion__subtitulo"><h2>Cuentas abiertas</h2><p>Selecciona la mesa o venta de mostrador a cobrar.</p></div><div class="savix-pos-lista"><article v-for="item in pedidos_abiertos" :key="item.id_pedido" class="savix-pos-lista__fila"><div><strong>{{ referenciaPedido(item) }}</strong><span>{{ item.tipo_servicio === 'mesa' ? 'Mesa' : 'Mostrador' }}</span></div><span>${{ Number(item.importe_total).toFixed(2) }}</span><button class="savix-pos-boton savix-pos-boton--primario" type="button" @click="abrirPedido(item)">Abrir cuenta</button></article><p v-if="!pedidos_abiertos.length" class="savix-pos-vacio">No hay cuentas abiertas para cobrar.</p></div></section>

            <section v-else class="savix-pos-seccion">
                <Link class="savix-pos-enlace" :href="`/caja?ref_sucursal=${sucursal_seleccionada.id_sucursal}`">Cambiar de cuenta</Link>
                <div class="savix-pos-seccion__subtitulo"><h2>Partes de cuenta</h2><p>Total de la cuenta: ${{ Number(pedido.importe_total).toFixed(2) }}</p></div>
                <div class="savix-pos-lista"><article v-for="parte in pedido.partes" :key="parte.id_parte_cuenta_pedido" class="savix-pos-lista__fila"><div><strong>{{ parte.nombre }}</strong><span>{{ parte.estado === 'pagada' ? 'Pagada' : 'Abierta' }}</span></div><span>${{ Number(parte.importe_total).toFixed(2) }}</span><button v-if="parte.estado === 'abierta' && Number(parte.importe_total) > 0" class="savix-pos-boton savix-pos-boton--primario" type="button" @click="prepararCobro(parte)">Cobrar parte</button><span v-else-if="parte.estado === 'abierta'">Sin consumo</span></article></div>
                <form class="savix-pos-formulario-interno" @submit.prevent="crearParte"><div class="savix-pos-seccion__subtitulo"><h2>Nueva parte</h2><p>Ejemplos: Persona 1, Ana o Amigos.</p></div><label class="savix-pos-campo"><span>Nombre</span><input v-model="nueva_parte.nombre" required></label><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="nueva_parte.processing">Crear parte</button></footer></form>
                <div class="savix-pos-seccion__subtitulo"><h2>Asignar productos</h2><p>Los productos enviados pueden moverse entre partes abiertas sin reenviar a preparación.</p></div><div class="savix-pos-lista"><article v-for="detalle in pedido.detalles" :key="detalle.id_detalle_ronda_pedido" class="savix-pos-lista__fila"><div><strong>{{ detalle.cantidad }} × {{ detalle.nombre_producto }}</strong><span>Ronda {{ detalle.numero_ronda }} · {{ detalle.estado_ronda }}</span></div><span>${{ Number(detalle.importe_total).toFixed(2) }}</span><select :value="detalle.ref_parte_cuenta_pedido" @change="asignarDetalle(detalle, $event.target.value)"><option v-for="parte in pedido.partes.filter((item) => item.estado === 'abierta' || item.id_parte_cuenta_pedido === detalle.ref_parte_cuenta_pedido)" :key="parte.id_parte_cuenta_pedido" :value="parte.id_parte_cuenta_pedido">{{ parte.nombre }}</option></select></article></div>
                <form v-if="parte_cobrando" class="savix-pos-formulario-interno" @submit.prevent="cobrar"><div class="savix-pos-seccion__subtitulo"><h2>Cobrar: {{ parte_cobrando.nombre }}</h2><p>Debe cubrir exactamente ${{ Number(parte_cobrando.importe_total).toFixed(2) }}.</p></div><div v-for="(pago, indice) in cobro.pagos" :key="indice" class="savix-pos-cuadricula savix-pos-cuadricula--tres"><label class="savix-pos-campo"><span>Forma</span><select v-model="pago.tipo_pago"><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option></select></label><label class="savix-pos-campo"><span>Importe</span><input v-model="pago.importe" type="number" min="0.01" step="0.01" required></label><label class="savix-pos-campo"><span>Referencia, opcional</span><input v-model="pago.referencia" maxlength="120"></label><button v-if="cobro.pagos.length > 1" class="savix-pos-enlace" type="button" @click="quitarPago(indice)">Quitar pago</button></div><p>Total capturado: ${{ totalPagos.toFixed(2) }}<span v-if="diferenciaPagos < -0.004"> · Faltan ${{ Math.abs(diferenciaPagos).toFixed(2) }}</span><span v-else-if="diferenciaPagos > 0.004"> · Excede por ${{ diferenciaPagos.toFixed(2) }}</span><span v-else> · Importe exacto</span></p><p v-if="cobro.errors.pagos" class="savix-pos-error">{{ cobro.errors.pagos }}</p><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="parte_cobrando = null">Cancelar</button><button class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="agregarPago">Agregar forma de pago</button><button class="savix-pos-boton savix-pos-boton--primario" :disabled="cobro.processing || !cobroExacto">Confirmar cobro</button></footer></form>
            </section>
        </section>
    </main>
</template>
